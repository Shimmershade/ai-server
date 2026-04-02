<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PingController;

Route::get('/ping', [PingController::class, 'ping']);
Route::post('/ping', [PingController::class, 'pingWithData']);
Route::get('/status', [PingController::class, 'status']);