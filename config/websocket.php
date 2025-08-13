<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebSocket Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Laravel Bref WebSocket
    | package. You can customize these settings based on your needs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | AWS Configuration
    |--------------------------------------------------------------------------
    |
    | AWS credentials and region configuration for API Gateway Management API.
    |
    */
    'aws_region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    
    'api_gateway_endpoint' => env('WEBSOCKET_API_GATEWAY_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | Connection Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for storing WebSocket connections. You can use cache
    | or database storage.
    |
    */
    'storage' => [
        'driver' => env('WEBSOCKET_STORAGE_DRIVER', 'cache'),
        'cache_prefix' => env('WEBSOCKET_CACHE_PREFIX', 'websocket'),
        'ttl' => env('WEBSOCKET_CACHE_TTL', 86400), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Broadcasting
    |--------------------------------------------------------------------------
    |
    | Enable or disable automatic event broadcasting for WebSocket events.
    |
    */
    'broadcast_events' => env('WEBSOCKET_BROADCAST_EVENTS', true),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Logging configuration for WebSocket events and errors.
    |
    */
    'logging' => [
        'enabled' => env('WEBSOCKET_LOGGING_ENABLED', true),
        'level' => env('WEBSOCKET_LOGGING_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration for WebSocket connections and messages.
    |
    */
    'rate_limiting' => [
        'enabled' => env('WEBSOCKET_RATE_LIMITING_ENABLED', false),
        'max_connections_per_minute' => env('WEBSOCKET_MAX_CONNECTIONS_PER_MINUTE', 60),
        'max_messages_per_minute' => env('WEBSOCKET_MAX_MESSAGES_PER_MINute', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Authentication configuration for WebSocket connections.
    |
    */
    'authentication' => [
        'enabled' => env('WEBSOCKET_AUTH_ENABLED', false),
        'guard' => env('WEBSOCKET_AUTH_GUARD', 'web'),
        'middleware' => env('WEBSOCKET_AUTH_MIDDLEWARE', 'auth:sanctum'),
    ],
];
