<?php

namespace App\Http\Middleware;

use App\Models\AccessLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditBffMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $hidden = ['password', 'password_confirmation', 'token', '_token'];
        $payload = Arr::except($request->all(), $hidden);

        $response = $next($request);

        $content = $response->getContent();
        $json = null;

        if (is_string($content) && $content !== '') {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $json = $decoded;
            }
        }

        $isOk = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        $audit = $json['meta']['audit'] ?? null;

        if (!$isOk || !$audit) {
            return $response;
        }

        // Failures are logged only when the controller explicitly opted in by passing audit
        // metadata to apiFail — a 422 from validation or a 404 from a bad id writes nothing.
        $failed = !$isOk;

        $module = $request->header('X-AUDIT-MODULE', $audit['module'] ?? 'General');
        $entity = $request->header('X-AUDIT-ENTITY', $audit['entity'] ?? null);

        $desc = $audit['description'] ?? null;
        if (!$desc) {
            $verb = match ($audit['event'] ?? null) {
                'created' => 'Recorded',
                'updated' => 'Updated',
                'deleted' => 'Deleted',
                default => 'Action',
            };
            $desc = $entity ? "{$verb} {$entity}" : $verb;
        }

        // Audit is best-effort: a logging failure must NEVER break the user's operation, which
        // has already succeeded. Wrap the write and swallow (but record) any error.
        try {
            AccessLog::create([
                // The acting tenant, from the session/token — the whole point of this change.
                // Nullable: a platform action with no tenant scope is still audited.
                'tenant_id' => function_exists('authTenantId') ? authTenantId() : null,

                // authUserId() returns the session UUID — NOT auth()->id(), which on the BFF is
                // the guard's integer id and would write a bad value into user_id (uuid).
                'user_id' => function_exists('authUserId') ? authUserId() : null,

                'module' => $module,
                'description' => $desc,
                'model_type' => $audit['model_type'] ?? null,
                'model_id' => $audit['model_id'] ?? $request->header('X-AUDIT-RECORD-ID'),
                'event' => $audit['event'] ?? strtolower($request->method()),
                'success' => !$failed,
                'payload' => $payload ?: null,
                'changes' => $audit['changes'] ?? null,
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit write failed (request unaffected)', [
                'error' => $e->getMessage(),
                'module' => $module,
                'path' => $request->path(),
            ]);
        }

        return $response;
    }
}