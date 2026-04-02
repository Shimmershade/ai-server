<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PingController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'pong',
            'timestamp' => now()->toISOString()
        ]);
    }

    public function pingWithData(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'pong',
            'received_data' => $request->all()
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'php_version' => phpversion(),
            'laravel_version' => app()->version()
        ]);
    }
}