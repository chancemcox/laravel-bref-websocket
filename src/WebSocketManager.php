<?php

namespace LaravelBref\WebSocket;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

class WebSocketManager
{
    /**
     * The WebSocket handler instance.
     */
    protected WebSocketHandler $handler;

    /**
     * Create a new WebSocket manager instance.
     */
    public function __construct(WebSocketHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Send a message to a specific connection.
     */
    public function sendToConnection(string $connectionId, array $data): bool
    {
        return $this->handler->sendToConnection($connectionId, $data);
    }

    /**
     * Send a message to multiple connections.
     */
    public function sendToConnections(array $connectionIds, array $data): array
    {
        return $this->handler->sendToConnections($connectionIds, $data);
    }

    /**
     * Broadcast a message to all connections.
     */
    public function broadcast(array $data): array
    {
        return $this->handler->broadcast($data);
    }

    /**
     * Send a notification to a specific user.
     */
    public function sendToUser(int $userId, array $data): array
    {
        $connections = $this->getConnectionsByUserId($userId);
        return $this->sendToConnections($connections, $data);
    }

    /**
     * Send a notification to multiple users.
     */
    public function sendToUsers(array $userIds, array $data): array
    {
        $results = [];
        
        foreach ($userIds as $userId) {
            $results[$userId] = $this->sendToUser($userId, $data);
        }

        return $results;
    }

    /**
     * Send a notification to users in a specific channel.
     */
    public function sendToChannel(string $channel, array $data): array
    {
        $connections = $this->getConnectionsByChannel($channel);
        return $this->sendToConnections($connections, $data);
    }

    /**
     * Join a user to a channel.
     */
    public function joinChannel(string $connectionId, string $channel): bool
    {
        $connection = $this->handler->getConnection($connectionId);
        
        if (!$connection) {
            return false;
        }

        $channels = Cache::get("websocket:channels:{$channel}", []);
        $channels[] = $connectionId;
        $channels = array_unique($channels);
        
        Cache::put("websocket:channels:{$channel}", $channels, now()->addDay());
        
        // Store channel info in connection
        $connection['channels'][] = $channel;
        Cache::put("websocket:connection:{$connectionId}", $connection, now()->addDay());

        Event::dispatch('websocket.channel.joined', [
            'connectionId' => $connectionId,
            'channel' => $channel,
        ]);

        return true;
    }

    /**
     * Leave a user from a channel.
     */
    public function leaveChannel(string $connectionId, string $channel): bool
    {
        $connection = $this->handler->getConnection($connectionId);
        
        if (!$connection) {
            return false;
        }

        // Remove from channel
        $channels = Cache::get("websocket:channels:{$channel}", []);
        $channels = array_filter($channels, fn($id) => $id !== $connectionId);
        Cache::put("websocket:channels:{$channel}", $channels, now()->addDay());

        // Remove channel from connection
        if (isset($connection['channels'])) {
            $connection['channels'] = array_filter($connection['channels'], fn($ch) => $ch !== $channel);
            Cache::put("websocket:connection:{$connectionId}", $connection, now()->addDay());
        }

        Event::dispatch('websocket.channel.left', [
            'connectionId' => $connectionId,
            'channel' => $channel,
        ]);

        return true;
    }

    /**
     * Get all connections for a specific user.
     */
    public function getConnectionsByUserId(int $userId): array
    {
        $connections = Cache::get('websocket:connections', []);
        $userConnections = [];

        foreach ($connections as $connectionId) {
            $connection = $this->handler->getConnection($connectionId);
            if ($connection && ($connection['user_id'] ?? null) === $userId) {
                $userConnections[] = $connectionId;
            }
        }

        return $userConnections;
    }

    /**
     * Get all connections in a specific channel.
     */
    public function getConnectionsByChannel(string $channel): array
    {
        return Cache::get("websocket:channels:{$channel}", []);
    }

    /**
     * Get all channels for a specific connection.
     */
    public function getChannelsByConnection(string $connectionId): array
    {
        $connection = $this->handler->getConnection($connectionId);
        return $connection['channels'] ?? [];
    }

    /**
     * Get all active channels.
     */
    public function getAllChannels(): array
    {
        $channels = [];
        $connections = Cache::get('websocket:connections', []);

        foreach ($connections as $connectionId) {
            $connection = $this->handler->getConnection($connectionId);
            if ($connection && isset($connection['channels'])) {
                $channels = array_merge($channels, $connection['channels']);
            }
        }

        return array_unique($channels);
    }

    /**
     * Get connection information.
     */
    public function getConnection(string $connectionId): ?array
    {
        return $this->handler->getConnection($connectionId);
    }

    /**
     * Check if a connection exists.
     */
    public function connectionExists(string $connectionId): bool
    {
        return $this->handler->connectionExists($connectionId);
    }

    /**
     * Get all active connections.
     */
    public function getAllConnections(): array
    {
        return Cache::get('websocket:connections', []);
    }

    /**
     * Get connection statistics.
     */
    public function getStats(): array
    {
        $connections = $this->getAllConnections();
        $channels = $this->getAllChannels();

        return [
            'total_connections' => count($connections),
            'total_channels' => count(array_unique($channels)),
            'channels' => array_count_values($channels),
        ];
    }

    /**
     * Handle a WebSocket event.
     */
    public function handle(array $event): array
    {
        return $this->handler->handle($event);
    }

    /**
     * Clean up expired connections.
     */
    public function cleanup(): int
    {
        $connections = $this->getAllConnections();
        $removed = 0;

        foreach ($connections as $connectionId) {
            $connection = $this->handler->getConnection($connectionId);
            
            if (!$connection || now()->diffInMinutes($connection['connected_at']) > 1440) {
                $this->handler->removeConnectionById($connectionId);
                $removed++;
            }
        }

        return $removed;
    }
}
