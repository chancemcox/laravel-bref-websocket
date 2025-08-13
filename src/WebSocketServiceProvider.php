<?php

namespace LaravelBref\WebSocket;

use Illuminate\Support\ServiceProvider;

class WebSocketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/websocket.php', 'websocket');

        $this->app->singleton(WebSocketManager::class, function ($app) {
            return new WebSocketManager($app->make(WebSocketHandler::class));
        });

        $this->app->alias(WebSocketManager::class, 'websocket');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/websocket.php' => $this->app->configPath('websocket.php'),
        ], 'config');
    }
}