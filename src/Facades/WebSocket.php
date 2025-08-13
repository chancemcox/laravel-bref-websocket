<?php

namespace LaravelBref\WebSocket\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool sendToConnection(string $connectionId, array $data)
 * @method static array sendToConnections(array $connectionIds, array $data)
 * @method static array broadcast(array $data)
 * @method static array|null getConnection(string $connectionId)
 * @method static bool connectionExists(string $connectionId)
 * @method static array getAllConnections()
 * @method static array handle(array $event)
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
