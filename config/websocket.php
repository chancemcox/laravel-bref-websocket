<?php

return [
    'api_gateway_endpoint' => env('WEBSOCKET_API_GATEWAY_ENDPOINT'),
    'aws_region' => env('WEBSOCKET_AWS_REGION', 'us-east-1'),

    // Optional: if you want to override credentials here instead of relying on default SDK provider chain
    'credentials' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],
];