<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LLMController;
use App\Http\Controllers\PingController;

Route::get('/ping', [PingController::class, 'ping']);
Route::post('/llm/ask', [LLMController::class, 'ask']);