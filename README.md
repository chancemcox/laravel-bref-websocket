# Laravel Bref WebSocket

A lightweight Laravel package that adds WebSocket support for AWS API Gateway WebSockets using the Bref serverless framework.

- PHP: ^8.0
- Laravel: ^9.0 | ^10.0 | ^11.0
- AWS SDK for PHP v3, Bref v2

## Installation

Install via Composer:

```bash
composer require laravel-bref/websocket
```

This package supports Laravel auto-discovery:
- Service Provider: `LaravelBref\WebSocket\WebSocketServiceProvider` (auto-discovered)
- Facade alias: `WebSocket` → `LaravelBref\WebSocket\Facades\WebSocket`

## Configuration

Set the following configuration values (via env or config), which are used by the AWS API Gateway Management API client:

- `websocket.api_gateway_endpoint` (required): The full API Gateway endpoint for your WebSocket stage, e.g. `https://abc123.execute-api.us-east-1.amazonaws.com/production`
- `websocket.aws_region` (default: `us-east-1`)
- Optional: `websocket.credentials.key` and `websocket.credentials.secret` (otherwise the AWS SDK default provider chain uses `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`)

Example `config/websocket.php` you can add to your app:

```php
<?php

return [
    'api_gateway_endpoint' => env('WEBSOCKET_API_GATEWAY_ENDPOINT'),
    'aws_region' => env('WEBSOCKET_AWS_REGION', 'us-east-1'),
];
```

And in your `.env`:

```dotenv
WEBSOCKET_API_GATEWAY_ENDPOINT=https://abc123.execute-api.us-east-1.amazonaws.com/production
WEBSOCKET_AWS_REGION=us-east-1
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret
```

## How it works

- `WebSocketHandler` encapsulates the low-level interaction with AWS API Gateway Management API and handles the incoming WebSocket events from API Gateway (`$connect`, `$disconnect`, `$default`, and any custom route).
- `WebSocketManager` provides your Laravel app with high-level operations: broadcasting messages, targeting connections/users/channels, channel membership, connection lookups, and simple statistics.
- `WebSocket` facade proxies to the bound `WebSocketManager` instance for convenient usage.

Connections and channel membership are tracked in Laravel Cache using the following keys:
- `websocket:connections` → array of all connection IDs
- `websocket:connection:{connectionId}` → connection payload array
- `websocket:channels:{channel}` → array of connection IDs in a channel

## Lambda handler (Bref)

Create a dedicated Lambda handler for your WebSocket routes. Example `handler.php`:

```php
<?php

use Bref\Context\Context;
use LaravelBref\WebSocket\WebSocketHandler;
use LaravelBref\WebSocket\WebSocketManager;

require __DIR__ . '/vendor/autoload.php';

$manager = new WebSocketManager(new WebSocketHandler());

return function (array $event, Context $context) use ($manager): array {
    return $manager->handle($event);
};
```

Example Serverless Framework snippet for a WebSocket API (simplified):

```yaml
functions:
  websocket:
    handler: handler.php
    runtime: php-82
    events:
      - websocket:
          route: $connect
      - websocket:
          route: $disconnect
      - websocket:
          route: $default
      - websocket:
          route: myRoute
```

In AWS API Gateway, ensure your stage URL matches `websocket.api_gateway_endpoint`.

## Events dispatched

You can listen to framework events for WebSocket lifecycle and message routing:
- `websocket.connected` → on `$connect`
- `websocket.disconnected` → on `$disconnect`
- `websocket.message` → on `$default`
- `websocket.{routeKey}` → for any custom route key
- `websocket.channel.joined` → when a connection joins a channel via `joinChannel`
- `websocket.channel.left` → when a connection leaves a channel via `leaveChannel`

Example listener registration:

```php
use Illuminate\Support\Facades\Event;

Event::listen('websocket.message', function (array $payload) {
    // $payload contains: connectionId, message (decoded JSON), eventData
});

Event::listen('websocket.myRoute', function (array $payload) {
    // Handle custom route messages
});
```

## Quick examples

Send to a single connection:

```php
use LaravelBref\WebSocket\Facades\WebSocket;

WebSocket::sendToConnection($connectionId, ['type' => 'ping']);
```

Broadcast to all connections:

```php
WebSocket::broadcast(['type' => 'announcement', 'message' => 'Hello everyone!']);
```

Join/leave channels and publish to a channel:

```php
$manager = app(\LaravelBref\WebSocket\WebSocketManager::class);

$manager->joinChannel($connectionId, 'news');
$manager->sendToChannel('news', ['title' => 'Breaking', 'body' => '...']);
$manager->leaveChannel($connectionId, 'news');
```

Target by user id (authorizer must set `requestContext.authorizer.userId`):

```php
$manager = app(\LaravelBref\WebSocket\WebSocketManager::class);

$manager->sendToUser(42, ['type' => 'personal', 'message' => 'Hi!']);
$manager->sendToUsers([1, 2, 3], ['type' => 'blast']);
```

Lookups and stats:

```php
$manager = app(\LaravelBref\WebSocket\WebSocketManager::class);

$exists = $manager->connectionExists($connectionId);
$info = $manager->getConnection($connectionId); // ['connection_id', 'connected_at', 'user_id', 'route_key', 'channels' => [...]]
$all = $manager->getAllConnections();
$channels = $manager->getAllChannels();
$stats = $manager->getStats(); // ['total_connections' => int, 'total_channels' => int, 'channels' => [channel => count]]
```

## API Reference

### Class: `LaravelBref\WebSocket\WebSocketManager`

- `__construct(WebSocketHandler $handler)`
  - Resolves via container; the facade binds to this manager under the key `websocket`.

- `sendToConnection(string $connectionId, array $data): bool`
  - Sends a JSON-encoded payload to a specific connection.

- `sendToConnections(array $connectionIds, array $data): array`
  - Sends to many connections; returns `[connectionId => bool]` results.

- `broadcast(array $data): array`
  - Sends to all active connections.

- `sendToUser(int $userId, array $data): array`
  - Sends to all connections associated with a user id; returns `[connectionId => bool]`.

- `sendToUsers(array $userIds, array $data): array`
  - Sends to multiple users; returns `[userId => [connectionId => bool]]`.

- `sendToChannel(string $channel, array $data): array`
  - Sends to all connections in a channel.

- `joinChannel(string $connectionId, string $channel): bool`
  - Adds a connection to a channel and persists membership. Dispatches `websocket.channel.joined`.

- `leaveChannel(string $connectionId, string $channel): bool`
  - Removes a connection from a channel. Dispatches `websocket.channel.left`.

- `getConnectionsByUserId(int $userId): array`
  - Returns connection IDs for the given user id.

- `getConnectionsByChannel(string $channel): array`
  - Returns connection IDs in the channel.

- `getChannelsByConnection(string $connectionId): array`
  - Returns channel names the connection has joined.

- `getAllChannels(): array`
  - Returns all unique channel names.

- `getConnection(string $connectionId): ?array`
  - Returns connection info or null.

- `connectionExists(string $connectionId): bool`
  - True if the connection exists.

- `getAllConnections(): array`
  - Returns all active connection IDs.

- `getStats(): array`
  - Returns aggregate counts: `total_connections`, `total_channels`, and `channels` map.

- `handle(array $event): array`
  - Forwards an incoming AWS WebSocket event to the handler.

- `cleanup(): int`
  - Scans known connections and removes stale ones (older than 24 hours). Returns number removed.

### Class: `LaravelBref\WebSocket\WebSocketHandler`

- `handle(array $event): array`
  - Parses API Gateway event (`requestContext.connectionId`, `requestContext.routeKey`, `body`) and dispatches lifecycle/message events. Returns `[statusCode, body]`.

- `sendToConnection(string $connectionId, array $data): bool`
  - Sends JSON-encoded data to a single connection using API Gateway Management API. Removes the connection if AWS returns `GoneException`.

- `sendToConnections(array $connectionIds, array $data): array`
  - Sends to multiple connections; returns `[connectionId => bool]` results.

- `broadcast(array $data): array`
  - Sends to all known connections.

- `getConnection(string $connectionId): ?array`
  - Reads connection info from cache.

- `connectionExists(string $connectionId): bool`
  - Checks cache for the given connection.

### Facade: `LaravelBref\WebSocket\Facades\WebSocket`

Static methods (proxy to the manager):
- `sendToConnection(string $connectionId, array $data): bool`
- `sendToConnections(array $connectionIds, array $data): array`
- `broadcast(array $data): array`
- `sendToUser(int $userId, array $data): array`
- `sendToUsers(array $userIds, array $data): array`
- `sendToChannel(string $channel, array $data): array`
- `joinChannel(string $connectionId, string $channel): bool`
- `leaveChannel(string $connectionId, string $channel): bool`
- `getConnectionsByUserId(int $userId): array`
- `getConnectionsByChannel(string $channel): array`
- `getChannelsByConnection(string $connectionId): array`
- `getAllChannels(): array`
- `getConnection(string $connectionId): array|null`
- `connectionExists(string $connectionId): bool`
- `getAllConnections(): array`
- `getStats(): array`
- `handle(array $event): array`
- `cleanup(): int`

Note: Additional manager methods are available by resolving the manager from the container: `app(LaravelBref\WebSocket\WebSocketManager::class)`.

## Error handling

- Failed sends are logged with context. If AWS returns `GoneException`, the connection is removed from the store automatically.
- Ensure your cache store is durable for your use case (e.g., Redis) to share connection state across executions.

## Security and authorizers

- If you need per-user routing, configure your WebSocket API to add `requestContext.authorizer.userId` (e.g., via a Lambda authorizer). The handler captures it to associate connections with users.

## Contributing

PRs are welcome! Please include tests where practical.

## License

MIT