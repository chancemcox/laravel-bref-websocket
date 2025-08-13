# Laravel Bref WebSocket

A Laravel package for WebSocket support using AWS API Gateway WebSockets and the Bref serverless framework.

## Features

- 🚀 **Serverless WebSockets** - Built for AWS Lambda with Bref
- 🔌 **Real-time Communication** - Bidirectional WebSocket connections
- 📡 **Event Broadcasting** - Laravel event system integration
- 🔐 **Authentication Support** - Built-in user authentication
- 📊 **Connection Management** - Track and manage active connections
- 🎯 **Targeted Messaging** - Send to specific users, channels, or broadcast
- 📝 **Comprehensive Logging** - Detailed logging and monitoring
- ⚡ **High Performance** - Optimized for serverless environments

## Requirements

- PHP 8.0+
- Laravel 9.0+ / 10.0+ / 11.0+
- Bref 2.0+
- AWS SDK for PHP 3.0+

## Installation

### 1. Install via Composer

```bash
composer require laravel-bref/websocket
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="LaravelBref\WebSocket\WebSocketServiceProvider" --tag="config"
```

### 3. Add Environment Variables

Add these to your `.env` file:

```env
AWS_DEFAULT_REGION=us-east-1
WEBSOCKET_API_GATEWAY_ENDPOINT=https://your-api-gateway-id.execute-api.us-east-1.amazonaws.com/dev
WEBSOCKET_STORAGE_DRIVER=cache
WEBSOCKET_CACHE_PREFIX=websocket
WEBSOCKET_LOGGING_ENABLED=true
```

## Configuration

The package configuration file `config/websocket.php` contains all the settings:

- AWS credentials and region
- Connection storage configuration
- Event broadcasting settings
- Logging configuration
- Rate limiting options
- Authentication settings

## Usage

### Basic WebSocket Handler

```php
<?php

namespace App\Http\Controllers;

use LaravelBref\WebSocket\Facades\WebSocket;

class WebSocketController extends Controller
{
    public function handle($event)
    {
        return WebSocket::handle($event);
    }
}
```

### Sending Messages

```php
// Send to specific connection
WebSocket::sendToConnection($connectionId, [
    'message' => 'Hello!',
    'type' => 'notification'
]);

// Send to multiple connections
WebSocket::sendToConnections([$conn1, $conn2], [
    'message' => 'Group message'
]);

// Broadcast to all connections
WebSocket::broadcast([
    'message' => 'System announcement',
    'type' => 'system'
]);

// Send to specific user
WebSocket::sendToUser($userId, [
    'message' => 'User-specific message'
]);

// Send to channel
WebSocket::sendToChannel('chat-room', [
    'message' => 'Room message'
]);
```

### Connection Management

```php
// Get connection info
$connection = WebSocket::getConnection($connectionId);

// Check if connection exists
if (WebSocket::connectionExists($connectionId)) {
    // Connection is active
}

// Get all connections
$allConnections = WebSocket::getAllConnections();
```

### Channel Management

```php
// Join a channel
WebSocket::joinChannel($connectionId, 'chat-room');

// Leave a channel
WebSocket::leaveChannel($connectionId, 'chat-room');

// Get channel connections
$channelConnections = WebSocket::getConnectionsByChannel('chat-room');
```

## Serverless Configuration

### serverless.yml

```yaml
service: laravel-websocket-app

provider:
  name: aws
  runtime: provided.al2
  region: us-east-1

plugins:
  - ./vendor/bref/bref

functions:
  websocket:
    handler: app/Http/Controllers/WebSocketController@handle
    events:
      - websocket:
          route: $connect
      - websocket:
          route: $disconnect
      - websocket:
          route: $default
```

### WebSocket Handler

```php
<?php

namespace App\Http\Controllers;

use LaravelBref\WebSocket\Facades\WebSocket;

class WebSocketController extends Controller
{
    public function handle($event)
    {
        return WebSocket::handle($event);
    }
}
```

## Events

The package dispatches several events you can listen to:

```php
// In your EventServiceProvider
protected $listen = [
    'websocket.connected' => [
        'App\Listeners\WebSocketConnectedListener',
    ],
    'websocket.disconnected' => [
        'App\Listeners\WebSocketDisconnectedListener',
    ],
    'websocket.message' => [
        'App\Listeners\WebSocketMessageListener',
    ],
];
```

## Testing

### Local Testing

```bash
# Start serverless offline
serverless offline start

# Test WebSocket connection
wscat -c ws://localhost:3001
```

### Unit Testing

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use LaravelBref\WebSocket\Facades\WebSocket;

class WebSocketTest extends TestCase
{
    public function test_websocket_connection()
    {
        $event = [
            'requestContext' => [
                'connectionId' => 'test-connection',
                'routeKey' => '$connect'
            ]
        ];

        $response = WebSocket::handle($event);
        
        $this->assertEquals(200, $response['statusCode']);
    }
}
```

## API Endpoints

The package provides several HTTP endpoints for management:

- `GET /websocket/status` - Get WebSocket service status
- `GET /websocket/connections` - List all active connections
- `POST /websocket/broadcast` - Send broadcast message
- `POST /websocket/send/{connectionId}` - Send message to specific connection

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

- [GitHub Issues](https://github.com/laravel-bref/websocket/issues)
- [Documentation](https://laravel-bref-websocket.readthedocs.io)
- [Discord Community](https://discord.gg/laravel)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.
