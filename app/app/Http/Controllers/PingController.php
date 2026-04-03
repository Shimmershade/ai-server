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
}