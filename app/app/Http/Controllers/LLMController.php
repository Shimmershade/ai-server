<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LLMController extends Controller
{
    public function ask(Request $request)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Api-Key ' . env('YANDEX_API_KEY'),
            'OpenAI-Project' => env('YANDEX_FOLDER_ID'),
        ])->post('https://ai.api.cloud.yandex.net/v1/responses', [
            'model' => 'gpt://' . env('YANDEX_FOLDER_ID') . '/deepseek-v32/latest',
            'instructions' => $request->input('instructions', ''),
            'input' => $request->input('input'),
            'temperature' => $request->input('temperature', 0.3),
            'max_output_tokens' => $request->input('max_tokens', 500)
        ]);
        
        return response()->json($response->json(), $response->status());
    }
}