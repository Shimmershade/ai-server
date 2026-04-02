<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function() {
    return ['message' => 'Test route works!'];
});

// временные

// Временные API маршруты в web.php
Route::get('/api/ping', function() {
    return response()->json([
        'status' => 'success',
        'message' => 'pong',
        'timestamp' => now()->toISOString()
    ]);
});

Route::post('/api/ping', function(Request $request) {
    return response()->json([
        'status' => 'success',
        'message' => 'pong',
        'received_data' => $request->all()
    ]);
});

Route::get('/api/status', function() {
    return response()->json([
        'status' => 'ok',
        'php_version' => phpversion(),
        'laravel_version' => app()->version()
    ]);
});