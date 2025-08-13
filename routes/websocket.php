<?php

use Illuminate\Support\Facades\Route;
use LaravelBref\WebSocket\WebSocketHandler;

/*
|--------------------------------------------------------------------------
| WebSocket Routes
|--------------------------------------------------------------------------
|
| These routes are used for WebSocket testing and management.
| They are loaded by the WebSocketServiceProvider.
|
*/

Route::prefix('websocket')->group(function () {
    Route::get('/status', function () {
        return response()->json([
            'status' => 'running',
            'timestamp' => now()->toISOString(),
            'connections' => app('websocket')->getAllConnections(),
        ]);
    })->name('websocket.status');

    Route::get('/connections', function () {
        return response()->json([
            'connections' => app('websocket')->getAllConnections(),
            'count' => count(app('websocket')->getAllConnections()),
        ]);
    })->name('websocket.connections');

    Route::post('/broadcast', function (Illuminate\Http\Request $request) {
        $data = $request->validate([
            'message' => 'required|string',
            'type' => 'nullable|string',
        ]);

        $result = app('websocket')->broadcast($data);

        return response()->json([
            'message' => 'Broadcast sent',
            'result' => $result,
        ]);
    })->name('websocket.broadcast');

    Route::post('/send/{connectionId}', function (Illuminate\Http\Request $request, string $connectionId) {
        $data = $request->validate([
            'message' => 'required|string',
            'type' => 'nullable|string',
        ]);

        $result = app('websocket')->sendToConnection($connectionId, $data);

        return response()->json([
            'message' => $result ? 'Message sent' : 'Failed to send message',
            'success' => $result,
        ]);
    })->name('websocket.send');
});
