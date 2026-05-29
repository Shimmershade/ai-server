<?php
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
            $response = Http::timeout(120)
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
                throw new \Exception('Yandex API error: ' . $response->body());
            }
            
            $data = $response->json();
            
            $answer = $data['output']['message']['content'] ?? $data['result']['message']['content'] ?? $data['text'] ?? null;
            
            if (!$answer) {
                throw new \Exception('Invalid response format from Yandex API');
            }
            
            return [
                'success' => true,
                'content' => $answer,
                'raw' => $data
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
}