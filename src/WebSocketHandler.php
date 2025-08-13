<?php

namespace LaravelBref\WebSocket;

use Aws\ApiGatewayManagementApi\ApiGatewayManagementApiClient;
use Aws\ApiGatewayManagementApi\Exception\ApiGatewayManagementApiException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class WebSocketHandler
{
    /**
     * The AWS API Gateway Management API client.
     */
    protected ApiGatewayManagementApiClient $apiGatewayClient;

    /**
     * The WebSocket connection ID.
     */
    protected string $connectionId;

    /**
     * The WebSocket route key.
     */
    protected string $routeKey;

    /**
     * The WebSocket event data.
     */
    protected array $eventData;

    /**
     * Create a new WebSocket handler instance.
     */
    public function __construct()
    {
        $options = [
            'version' => 'latest',
            'region' => config('websocket.aws_region', 'us-east-1'),
            'endpoint' => config('websocket.api_gateway_endpoint'),
        ];

        $websocketCredentials = config('websocket.credentials');
        if (is_array($websocketCredentials)
            && !empty($websocketCredentials['key'])
            && !empty($websocketCredentials['secret'])) {
            $options['credentials'] = [
                'key' => $websocketCredentials['key'],
                'secret' => $websocketCredentials['secret'],
            ];
        }

        $this->apiGatewayClient = new ApiGatewayManagementApiClient($options);
    }

    /**
     * Handle the WebSocket event.
     */
    public function handle(array $event): array
    {
        $this->connectionId = $event['requestContext']['connectionId'] ?? '';
        $this->routeKey = $event['requestContext']['routeKey'] ?? '';
        $this->eventData = $event;

        Log::info('WebSocket event received', [
            'connectionId' => $this->connectionId,
            'routeKey' => $this->routeKey,
            'event' => $event,
        ]);

        try {
            return match ($this->routeKey) {
                '$connect' => $this->handleConnect(),
                '$disconnect' => $this->handleDisconnect(),
                '$default' => $this->handleDefault(),
                default => $this->handleCustomRoute(),
            };
        } catch (\Exception $e) {
            Log::error('WebSocket handler error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'statusCode' => 500,
                'body' => json_encode(['error' => 'Internal server error']),
            ];
        }
    }

    /**
     * Handle WebSocket connection.
     */
    protected function handleConnect(): array
    {
        Event::dispatch('websocket.connected', [
            'connectionId' => $this->connectionId,
            'eventData' => $this->eventData,
        ]);

        // Store connection in database or cache
        $this->storeConnection();

        return [
            'statusCode' => 200,
            'body' => json_encode(['message' => 'Connected']),
        ];
    }

    /**
     * Handle WebSocket disconnection.
     */
    protected function handleDisconnect(): array
    {
        Event::dispatch('websocket.disconnected', [
            'connectionId' => $this->connectionId,
            'eventData' => $this->eventData,
        ]);

        // Remove connection from database or cache
        $this->removeConnection();

        return [
            'statusCode' => 200,
            'body' => json_encode(['message' => 'Disconnected']),
        ];
    }

    /**
     * Handle default WebSocket messages.
     */
    protected function handleDefault(): array
    {
        $body = json_decode($this->eventData['body'] ?? '{}', true);
        
        Event::dispatch('websocket.message', [
            'connectionId' => $this->connectionId,
            'message' => $body,
            'eventData' => $this->eventData,
        ]);

        return [
            'statusCode' => 200,
            'body' => json_encode(['message' => 'Message received']),
        ];
    }

    /**
     * Handle custom route messages.
     */
    protected function handleCustomRoute(): array
    {
        $body = json_decode($this->eventData['body'] ?? '{}', true);
        
        Event::dispatch("websocket.{$this->routeKey}", [
            'connectionId' => $this->connectionId,
            'message' => $body,
            'eventData' => $this->eventData,
        ]);

        return [
            'statusCode' => 200,
            'body' => json_encode(['message' => 'Custom route handled']),
        ];
    }

    /**
     * Send a message to a specific connection.
     */
    public function sendToConnection(string $connectionId, array $data): bool
    {
        try {
            $this->apiGatewayClient->postToConnection([
                'ConnectionId' => $connectionId,
                'Data' => json_encode($data),
            ]);

            return true;
        } catch (ApiGatewayManagementApiException $e) {
            if ($e->getAwsErrorCode() === 'GoneException') {
                // Connection is no longer valid, remove it
                $this->removeConnectionById($connectionId);
            }
            
            Log::error('Failed to send WebSocket message', [
                'connectionId' => $connectionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a message to multiple connections.
     */
    public function sendToConnections(array $connectionIds, array $data): array
    {
        $results = [];
        
        foreach ($connectionIds as $connectionId) {
            $results[$connectionId] = $this->sendToConnection($connectionId, $data);
        }

        return $results;
    }

    /**
     * Broadcast a message to all connections.
     */
    public function broadcast(array $data): array
    {
        $connections = $this->getAllConnections();
        return $this->sendToConnections($connections, $data);
    }

    /**
     * Store a new connection.
     */
    protected function storeConnection(): void
    {
        $connection = [
            'connection_id' => $this->connectionId,
            'connected_at' => now(),
            'user_id' => $this->eventData['requestContext']['authorizer']['userId'] ?? null,
            'route_key' => $this->routeKey,
        ];

        // Store in cache or database
        cache()->put("websocket:connection:{$this->connectionId}", $connection, now()->addDay());
        
        // Add to connection list
        $connections = cache()->get('websocket:connections', []);
        $connections[] = $this->connectionId;
        cache()->put('websocket:connections', $connections, now()->addDay());
    }

    /**
     * Remove a connection.
     */
    protected function removeConnection(): void
    {
        $this->removeConnectionById($this->connectionId);
    }

    /**
     * Remove a connection by ID.
     */
    protected function removeConnectionById(string $connectionId): void
    {
        // Remove from cache
        cache()->forget("websocket:connection:{$connectionId}");
        
        // Remove from connection list
        $connections = cache()->get('websocket:connections', []);
        $connections = array_filter($connections, fn($id) => $id !== $connectionId);
        cache()->put('websocket:connections', $connections, now()->addDay());
    }

    /**
     * Get all active connections.
     */
    protected function getAllConnections(): array
    {
        return cache()->get('websocket:connections', []);
    }

    /**
     * Get connection information.
     */
    public function getConnection(string $connectionId): ?array
    {
        return cache()->get("websocket:connection:{$connectionId}");
    }

    /**
     * Check if a connection exists.
     */
    public function connectionExists(string $connectionId): bool
    {
        return cache()->has("websocket:connection:{$connectionId}");
    }
}
