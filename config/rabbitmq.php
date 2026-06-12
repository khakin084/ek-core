<?php

return [
    'host' => env('RABBITMQ_HOST', 'ek-event-bus'),
    'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    'events_exchange' => env('RABBITMQ_EVENTS_EXCHANGE', 'ek.events'),
];