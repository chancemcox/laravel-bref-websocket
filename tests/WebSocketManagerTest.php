<?php

namespace LaravelBref\WebSocket\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use LaravelBref\WebSocket\WebSocketHandler;
use LaravelBref\WebSocket\WebSocketManager;
use LaravelBref\WebSocket\WebSocketServiceProvider;

class WebSocketManagerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [WebSocketServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('websocket.api_gateway_endpoint', 'https://example.com/production');
        $app['config']->set('websocket.aws_region', 'us-east-1');
    }

    public function test_join_and_leave_channel_updates_cache_and_events(): void
    {
        $handler = $this->app->make(WebSocketHandler::class);
        $manager = $this->app->make(WebSocketManager::class);

        // Seed a fake connection
        $connectionId = 'conn-123';
        Cache::put("websocket:connection:{$connectionId}", [
            'connection_id' => $connectionId,
            'connected_at' => now(),
            'user_id' => 42,
            'route_key' => '$connect',
            'channels' => [],
        ], now()->addDay());
        Cache::put('websocket:connections', [$connectionId], now()->addDay());

        Event::fake();

        $resultJoin = $manager->joinChannel($connectionId, 'news');
        $this->assertTrue($resultJoin);
        $this->assertSame([$connectionId], Cache::get("websocket:channels:news"));
        $this->assertSame(['news'], $manager->getChannelsByConnection($connectionId));
        Event::assertDispatched('websocket.channel.joined');

        $resultLeave = $manager->leaveChannel($connectionId, 'news');
        $this->assertTrue($resultLeave);
        $this->assertSame([], Cache::get("websocket:channels:news"));
        $this->assertSame([], $manager->getChannelsByConnection($connectionId));
        Event::assertDispatched('websocket.channel.left');
    }

    public function test_get_connections_by_user_and_channel(): void
    {
        $manager = $this->app->make(WebSocketManager::class);

        $connA = 'A';
        $connB = 'B';

        Cache::put("websocket:connection:{$connA}", [
            'connection_id' => $connA,
            'connected_at' => now(),
            'user_id' => 1,
            'route_key' => '$connect',
            'channels' => ['news'],
        ], now()->addDay());
        Cache::put("websocket:connection:{$connB}", [
            'connection_id' => $connB,
            'connected_at' => now(),
            'user_id' => 2,
            'route_key' => '$connect',
            'channels' => ['sports'],
        ], now()->addDay());
        Cache::put('websocket:connections', [$connA, $connB], now()->addDay());
        Cache::put('websocket:channels:news', [$connA], now()->addDay());
        Cache::put('websocket:channels:sports', [$connB], now()->addDay());

        $this->assertSame([$connA], $manager->getConnectionsByUserId(1));
        $this->assertSame([$connB], $manager->getConnectionsByUserId(2));
        $this->assertSame([$connA], $manager->getConnectionsByChannel('news'));
        $this->assertSame([$connB], $manager->getConnectionsByChannel('sports'));
    }

    public function test_cleanup_removes_stale_connections(): void
    {
        $manager = $this->app->make(WebSocketManager::class);

        $fresh = 'fresh-1';
        $stale = 'stale-1';

        Cache::put("websocket:connection:{$fresh}", [
            'connection_id' => $fresh,
            'connected_at' => now(),
            'channels' => [],
        ], now()->addDay());

        Cache::put("websocket:connection:{$stale}", [
            'connection_id' => $stale,
            'connected_at' => now()->subDays(2),
            'channels' => [],
        ], now()->addDay());

        Cache::put('websocket:connections', [$fresh, $stale], now()->addDay());

        $removed = $manager->cleanup();
        $this->assertSame(1, $removed);
        $this->assertTrue(Cache::has("websocket:connection:{$fresh}"));
        $this->assertFalse(Cache::has("websocket:connection:{$stale}"));
    }
}