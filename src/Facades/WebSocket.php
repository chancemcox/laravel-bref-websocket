<?php

namespace LaravelBref\WebSocket\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool sendToConnection(string $connectionId, array $data)
 * @method static array sendToConnections(array $connectionIds, array $data)
 * @method static array broadcast(array $data)
 * @method static array sendToUser(int $userId, array $data)
 * @method static array sendToUsers(array $userIds, array $data)
 * @method static array sendToChannel(string $channel, array $data)
 * @method static bool joinChannel(string $connectionId, string $channel)
 * @method static bool leaveChannel(string $connectionId, string $channel)
 * @method static array getConnectionsByUserId(int $userId)
 * @method static array getConnectionsByChannel(string $channel)
 * @method static array getChannelsByConnection(string $connectionId)
 * @method static array getAllChannels()
 * @method static array|null getConnection(string $connectionId)
 * @method static bool connectionExists(string $connectionId)
 * @method static array getAllConnections()
 * @method static array getStats()
 * @method static array handle(array $event)
 * @method static int cleanup()
 *
 * @see \LaravelBref\WebSocket\WebSocketManager
 */
class WebSocket extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'websocket';
    }
}
