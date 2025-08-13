<?php

namespace LaravelBref\WebSocket;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use LaravelBref\WebSocket\WebSocketHandler;
use LaravelBref\WebSocket\WebSocketManager;

class WebSocketServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('websocket', function ($app) {
            $handler = new WebSocketHandler();
            return new WebSocketManager($handler);
        });

        $this->app->singleton(WebSocketHandler::class, function ($app) {
            return new WebSocketHandler();
        });

        $this->app->singleton(WebSocketManager::class, function ($app) {
            $handler = $app->make(WebSocketHandler::class);
            return new WebSocketManager($handler);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/websocket.php' => config_path('websocket.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__ . '/../routes/websocket.php');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'websocket',
            WebSocketHandler::class,
            WebSocketManager::class,
        ];
    }
}
