<?php

namespace LaravelBref\WebSocket\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use LaravelBref\WebSocket\WebSocketHandler;
use LaravelBref\WebSocket\WebSocketServiceProvider;

class WebSocketHandlerTest extends TestCase
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

    public function test_handle_connect_and_disconnect(): void
    {
        Event::fake();
        $handler = $this->app->make(WebSocketHandler::class);

        $connectEvent = [
            'requestContext' => [
                'connectionId' => 'abc',
                'routeKey' => '$connect',
                'authorizer' => ['userId' => 7],
            ],
        ];

        $response = $handler->handle($connectEvent);
        $this->assertSame(200, $response['statusCode']);
        $this->assertTrue(Cache::has('websocket:connection:abc'));
        $this->assertContains('abc', Cache::get('websocket:connections'));
        Event::assertDispatched('websocket.connected');

        $disconnectEvent = [
            'requestContext' => [
                'connectionId' => 'abc',
                'routeKey' => '$disconnect',
            ],
        ];

        $response = $handler->handle($disconnectEvent);
        $this->assertSame(200, $response['statusCode']);
        $this->assertFalse(Cache::has('websocket:connection:abc'));
        $this->assertNotContains('abc', Cache::get('websocket:connections'));
        Event::assertDispatched('websocket.disconnected');
    }

    public function test_handle_default_and_custom_route_dispatches_events(): void
    {
        Event::fake();
        $handler = $this->app->make(WebSocketHandler::class);

        $defaultEvent = [
            'requestContext' => [
                'connectionId' => 'x',
                'routeKey' => '$default',
            ],
            'body' => json_encode(['foo' => 'bar']),
        ];

        $response = $handler->handle($defaultEvent);
        $this->assertSame(200, $response['statusCode']);
        Event::assertDispatched('websocket.message');

        $customEvent = [
            'requestContext' => [
                'connectionId' => 'x',
                'routeKey' => 'myRoute',
            ],
            'body' => json_encode(['hello' => 'world']),
        ];

        $response = $handler->handle($customEvent);
        $this->assertSame(200, $response['statusCode']);
        Event::assertDispatched('websocket.myRoute');
    }
}