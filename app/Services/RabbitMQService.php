<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;

class RabbitMQService
{
    protected ?AMQPStreamConnection $connection = null;
    protected bool $confirmMode;
    protected bool $isConnected = false;

    // --- Defaults / knobs ---
    protected string $defaultExchange;
    protected string $sourceService;
    protected string $environment;
    protected string $tenantId;

    protected int $publishRetryMax = 1;           // reconnect+retry once
    protected int $consumePrefetch = 10;
    protected int $idempotencyTtlSeconds = 86400; // 24h
    protected bool $requeueOnFailure = true;      // set false if you use DLQ

    public function __construct()
    {
        $this->confirmMode = (bool) env('RABBITMQ_CONFIRM_MODE', false);

        // Pull from your config; keep compatibility with your existing eventbus config
        $this->defaultExchange = config('eventbus.exchange', env('RABBITMQ_EVENTS_EXCHANGE', 'ek.events'));

        $this->sourceService = env('APP_NAME', 'unknown-service');
        $this->environment = env('APP_ENV', 'dev');
        $this->tenantId = env('EK_TENANT_ID', 'ek-default');

        try {
            $this->connect();
        } catch (\Throwable $e) {
            $this->isConnected = false;
            Log::warning('RabbitMQ connection failed on construct', ['error' => $e->getMessage()]);
        }
    }

    private function connect(): void
    {
        if ($this->connection && $this->connection->isConnected()) {
            $this->isConnected = true;
            return;
        }

        $this->connection = new AMQPStreamConnection(
            config('eventbus.host'),
            config('eventbus.port'),
            config('eventbus.user'),
            config('eventbus.password'),
            config('eventbus.vhost', '/')
        );

        $this->isConnected = true;
    }

    public function isAvailable(): bool
    {
        return $this->isConnected && $this->connection && $this->connection->isConnected();
    }

    public function reconnect(): void
    {
        if ($this->connection) {
            try { $this->connection->close(); } catch (\Throwable) {}
        }

        $this->connection = null;
        $this->isConnected = false;

        // small backoff
        usleep(500_000);

        $this->connect();
    }

    /**
     * Publish raw payload (backwards compatible with your existing usage).
     * If you're publishing domain events, prefer publishEvent().
     */
    public function publish(string $exchange, string $routingKey, array $data, array $properties = []): void
    {
        $this->publishInternal($exchange, $routingKey, $data, $properties);
    }

    /**
     * Publish canonical event envelope (recommended).
     *
     * @param string $type    e.g. ek.orders.order.created
     * @param string $subject e.g. orders/229
     * @param array  $data    business payload
     * @param array  $meta    correlation_id, causation_id, actor, schema_version, tenant_id, environment
     */
    public function publishEvent(
        string $type,
        string $subject,
        array $data,
        array $meta = [],
        ?string $exchange = null
    ): string {
        $exchange = $exchange ?: $this->defaultExchange;

        $event = $this->buildEnvelope($type, $subject, $data, $meta);
        $routingKey = $type; // simple: routing key equals event type

        $headers = [
            'event_id' => $event['id'],
            'event_type' => $event['type'],
            'source' => $event['source'],
            'subject' => $event['subject'],
            'correlation_id' => $event['correlation_id'],
            'causation_id' => $event['causation_id'] ?? null,
            'schema_version' => $event['schema_version'],
            'tenant_id' => $event['tenant_id'],
            'environment' => $event['environment'],
        ];

        $this->publishInternal(
            $exchange,
            $routingKey,
            $event,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'timestamp' => time(),
                'application_headers' => new AMQPTable(array_filter($headers, fn($v) => $v !== null)),
            ]
        );

        return $event['id'];
    }

    /**
     * Consume messages from a queue bound to exchange/bindingKey with:
     * - JSON envelope parsing (optional)
     * - correlation id logging context
     * - idempotency by event.id (if present)
     */
    public function consume(
        string $exchange,
        string $queueName,
        string $bindingKey,
        callable $callback,
        array $options = []
    ): void {
        if (!$this->isAvailable()) {
            throw new \RuntimeException("RabbitMQ connection not available");
        }

        $consumerName = $options['consumer_name'] ?? $queueName;
        $prefetch = (int)($options['prefetch'] ?? $this->consumePrefetch);
        $idempotent = (bool)($options['idempotent'] ?? true);
        $requeueOnFailure = (bool)($options['requeue_on_failure'] ?? $this->requeueOnFailure);

        $channel = null;

        try {
            $channel = $this->connection->channel();

            // Ensure exchange exists
            $channel->exchange_declare($exchange, 'topic', false, true, false);

            // Durable queue
            $channel->queue_declare($queueName, false, true, false, false);

            // Bind
            $channel->queue_bind($queueName, $exchange, $bindingKey);

            // Prefetch for throughput control
            $channel->basic_qos(0, $prefetch, null);

            $channel->basic_consume(
                $queueName,
                '',
                false,
                false,
                false,
                false,
                function (AMQPMessage $msg) use ($callback, $consumerName, $idempotent, $requeueOnFailure) {

                    $body = $msg->getBody();
                    $decoded = json_decode($body, true);

                    // If it's JSON, treat it as an event envelope; else pass raw msg to callback
                    $event = is_array($decoded) ? $decoded : null;

                    $eventId = $event['id'] ?? null;
                    $eventType = $event['type'] ?? null;
                    $correlationId = $event['correlation_id']
                        ?? $this->headerFromMessage($msg, 'correlation_id')
                        ?? $this->headerFromMessage($msg, 'X-Correlation-Id')
                        ?? null;

                    Log::withContext([
                        'consumer' => $consumerName,
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'correlation_id' => $correlationId,
                    ]);

                    // Idempotency (only when event.id exists)
                    if ($idempotent && $eventId) {
                        $key = $this->idempotencyKey($consumerName, $eventId);

                        if (Cache::has($key)) {
                            Log::info('Duplicate event ignored (idempotent).');
                            $msg->ack();
                            return;
                        }

                        try {
                            $callback($msg, $event); // pass both
                            Cache::put($key, true, $this->idempotencyTtlSeconds);
                            $msg->ack();
                            Log::info('Event processed successfully.');
                            return;
                        } catch (\Throwable $e) {
                            Log::error('Event processing failed.', [
                                'error' => $e->getMessage(),
                                'exception' => get_class($e),
                            ]);

                            // Do not mark idempotency key
                            $msg->nack(false, $requeueOnFailure);
                            return;
                        }
                    }

                    // Non-idempotent path (or missing eventId)
                    try {
                        $callback($msg, $event);
                        $msg->ack();
                    } catch (\Throwable $e) {
                        Log::error('Message processing failed.', [
                            'error' => $e->getMessage(),
                            'exception' => get_class($e),
                        ]);
                        $msg->nack(false, $requeueOnFailure);
                    }
                }
            );

            register_shutdown_function(function () use ($channel) {
                try { $channel?->close(); } catch (\Throwable) {}
                try { $this->connection?->close(); } catch (\Throwable) {}
            });

            while ($channel->is_consuming()) {
                $channel->wait(null, false, 5);
            }
        } catch (\Throwable $e) {
            Log::error('RabbitMQ consume loop failed', [
                'queue' => $queueName,
                'exchange' => $exchange,
                'bindingKey' => $bindingKey,
                'error' => $e->getMessage(),
            ]);
        } finally {
            if ($channel) {
                try { $channel->close(); } catch (\Throwable) {}
            }
        }
    }

    // ---------------- Internal helpers ----------------

    private function publishInternal(string $exchange, string $routingKey, array $data, array $properties = []): void
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException("RabbitMQ connection not available");
        }

        $attempt = 0;

        while (true) {
            $attempt++;
            $channel = null;

            try {
                $channel = $this->connection->channel();
                $channel->exchange_declare($exchange, 'topic', false, true, false);

                if ($this->confirmMode) {
                    $channel->confirm_select();
                }

                $payload = json_encode($data, JSON_UNESCAPED_SLASHES);

                $defaultProperties = [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'timestamp' => time(),
                ];

                $message = new AMQPMessage($payload, array_merge($defaultProperties, $properties));

                $channel->basic_publish($message, $exchange, $routingKey);

                if ($this->confirmMode) {
                    $channel->wait_for_pending_acks_returns(3);
                }

                return;
            } catch (AMQPConnectionClosedException $e) {
                Log::warning('RabbitMQ connection closed during publish; reconnecting...', [
                    'attempt' => $attempt,
                    'exchange' => $exchange,
                    'routingKey' => $routingKey,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt > $this->publishRetryMax + 1) {
                    throw new \RuntimeException("Message publishing failed after retry: {$e->getMessage()}");
                }

                $this->reconnect();
            } catch (\Throwable $e) {
                Log::error('RabbitMQ publish failed', [
                    'exchange' => $exchange,
                    'routingKey' => $routingKey,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);

                throw new \RuntimeException("Message publishing failed: " . $e->getMessage());
            } finally {
                if ($channel) {
                    try { $channel->close(); } catch (\Throwable) {}
                }
            }
        }
    }

    public function buildEnvelope(string $type, string $subject, array $data, array $meta = []): array
    {
        $correlationId = $meta['correlation_id']
            ?? request()?->header('X-Correlation-Id')
            ?? (string) Str::uuid();

        $eventId = $meta['id'] ?? (string) Str::ulid();

        $envelope = [
            'specversion'     => '1.0',
            'id'              => $eventId,
            'type'            => $type,
            'source'          => $meta['source'] ?? $this->sourceService,
            'subject'         => $subject,
            'time'            => now('UTC')->toISOString(),
            'datacontenttype' => 'application/json',

            'correlation_id'  => $correlationId,
            'causation_id'    => $meta['causation_id'] ?? null,
            'tenant_id'       => $meta['tenant_id'] ?? $this->tenantId,
            'environment'     => $meta['environment'] ?? $this->environment,
            'schema_version'  => (int)($meta['schema_version'] ?? 1),

            'actor'           => $meta['actor'] ?? $this->defaultActor(),
            'trace'           => $meta['trace'] ?? null,

            'data'            => $data,
        ];

        return array_filter($envelope, fn($v) => $v !== null);
    }

    private function defaultActor(): array
    {
        $user = auth()->user();
        if ($user && isset($user->id)) {
            return ['type' => 'user', 'id' => (string) $user->id];
        }
        return ['type' => 'service', 'id' => $this->sourceService];
    }

    private function idempotencyKey(string $consumerName, string $eventId): string
    {
        return "evt:{$consumerName}:{$eventId}";
    }

    private function headerFromMessage(AMQPMessage $msg, string $key): ?string
    {
        $props = $msg->get_properties();
        $headers = $props['application_headers'] ?? null;

        if (!$headers instanceof AMQPTable) {
            return null;
        }

        $native = $headers->getNativeData();
        $val = $native[$key] ?? null;

        return is_scalar($val) ? (string)$val : null;
    }

    public function __destruct()
    {
        if ($this->connection && $this->isConnected) {
            try { $this->connection->close(); } catch (\Throwable) {}
        }
    }
}

// Publish a canonical event
// app(RabbitMQService::class)->publishEvent(
//     type: 'ek.orders.order.created',
//     subject: 'orders/229',
//     data: ['order_id' => 229, 'order_type' => 'SALES', 'status' => 'QUOTE'],
//     meta: ['causation_id' => request()->header('X-Causation-Id')]
// );

// Consume with idempotency + correlation IDs
// app(RabbitMQService::class)->consume(
//     exchange: 'ek.events',
//     queueName: 'ek.reporting.queue',
//     bindingKey: 'ek.#',
//     callback: function (AMQPMessage $msg, ?array $event) {
//         // $event is the decoded envelope (or null if non-json)
//         // do your work...
//     },
//     options: [
//         'consumer_name' => 'reporting-all-events',
//         'idempotent' => true,
//         'prefetch' => 10,
//         'requeue_on_failure' => true, // set false if you use DLQ
//     ]
// );