<?php
// app/Services/LLM/YandexLLMClient.php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class YandexLLMClient
{
    private string $apiKey;
    private string $folderId;
    private string $modelUri;
    
    public function __construct()
    {
        $this->apiKey = env('YANDEX_API_KEY');
        $this->folderId = env('YANDEX_FOLDER_ID');
        $this->modelUri = 'gpt://' . $this->folderId . '/deepseek-v32/latest';
    }
    
    public function ask(string $instructions, string $input, float $temperature = 0.3, int $maxTokens = 500): array
    {
        try {
            $response = Http::timeout(60)
                ->retry(3, 100)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Api-Key ' . $this->apiKey,
                    'OpenAI-Project' => $this->folderId,
                ])
                ->post('https://ai.api.cloud.yandex.net/v1/responses', [
                    'model' => $this->modelUri,
                    'instructions' => $instructions,
                    'input' => $input,
                    'temperature' => $temperature,
                    'max_output_tokens' => $maxTokens,
                ]);
            
            if ($response->failed()) {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status() . ' - ' . $response->body()
                ];
            }
            
            $fullResponse = $response->json();
            
            // Парсим ответ в формате Yandex API
            $answer = $this->extractAnswerFromYandexResponse($fullResponse);
            
            if ($answer) {
                return [
                    'success' => true,
                    'content' => $answer,
                    'raw' => $fullResponse
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Could not extract answer from Yandex API response',
                'debug_response' => $fullResponse
            ];
            
        } catch (RequestException $e) {
            return [
                'success' => false,
                'error' => 'Request failed: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Извлекает текст ответа из структуры Yandex API
     * 
     * Структура ответа:
     * {
     *   "output": [
     *     { "type": "reasoning", ... },
     *     {
     *       "type": "message",
     *       "content": [
     *         { "type": "output_text", "text": "..." }
     *       ]
     *     }
     *   ]
     * }
     */
    private function extractAnswerFromYandexResponse(array $response): ?string
    {
        // Проверяем наличие output массива
        if (!isset($response['output']) || !is_array($response['output'])) {
            return null;
        }
        
        // Ищем в output сообщение типа "message"
        foreach ($response['output'] as $outputItem) {
            if (isset($outputItem['type']) && $outputItem['type'] === 'message') {
                // Проверяем content массив
                if (isset($outputItem['content']) && is_array($outputItem['content'])) {
                    foreach ($outputItem['content'] as $contentItem) {
                        // Ищем текст в output_text
                        if (isset($contentItem['type']) && $contentItem['type'] === 'output_text') {
                            if (isset($contentItem['text'])) {
                                return $contentItem['text'];
                            }
                        }
                        // Альтернативный формат
                        if (isset($contentItem['text'])) {
                            return $contentItem['text'];
                        }
                    }
                }
            }
        }
        
        // Fallback: ищем любой текст рекурсивно
        return $this->findAnyText($response);
    }
    
    /**
     * Рекурсивный поиск текста в ответе (fallback)
     */
    private function findAnyText(array $data, int $depth = 0): ?string
    {
        if ($depth > 5) {
            return null;
        }
        
        foreach ($data as $key => $value) {
            // Ищем ключи, которые могут содержать текст
            if (in_array($key, ['text', 'content', 'message']) && is_string($value) && strlen($value) > 10) {
                return $value;
            }
            
            if (is_array($value)) {
                $result = $this->findAnyText($value, $depth + 1);
                if ($result) {
                    return $result;
                }
            }
        }
        
        return null;
    }
}