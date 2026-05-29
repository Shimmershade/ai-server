<?php

namespace App\Http\Controllers;

use App\Enums\RequestType;
use App\Http\Requests\LLMRequest;
use App\Services\LLM\YandexLLMClient;
use App\Services\LLM\UniversalPromptBuilder;
use App\Services\LLM\UniversalResponseParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LLMController extends Controller
{
    public function __construct(
        private YandexLLMClient $llmClient,
        private UniversalPromptBuilder $promptBuilder,
        private UniversalResponseParser $responseParser
    ) {}
    
    /**
     * Основной метод обработки запросов к ИИ
     */
    public function ask(LLMRequest $request): JsonResponse
    {
        $type = RequestType::from($request->input('request_type'));
        $temperature = $request->input('temperature', $this->getDefaultTemperature($type));
        $maxTokens = $request->input('max_tokens', $this->getDefaultMaxTokens($type));
        
        try {
            // 1. Строим промпты
            $instructions = $this->promptBuilder->buildInstructions($type);
            $input = $this->promptBuilder->buildInput($type, $request->validated());
            
            // 2. Отправляем запрос к Yandex GPT
            $llmResult = $this->llmClient->ask($instructions, $input, $temperature, $maxTokens);
            
            if (!$llmResult['success']) {
                Log::error('LLM request failed', ['error' => $llmResult['error'], 'type' => $type->value]);
                return $this->errorResponse('Ошибка при обращении к ИИ: ' . $llmResult['error']);
            }
            
            // 3. Парсим ответ
            $parsedResponse = $this->responseParser->parse(
                $type,
                $llmResult['content'],
                $request->validated()
            );
            
            // 4. Логируем успешный запрос (опционально)
            Log::info('LLM request successful', [
                'type' => $type->value,
                'tokens' => $maxTokens,
                'temperature' => $temperature
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $parsedResponse
            ]);
            
        } catch (\InvalidArgumentException $e) {
            Log::error('Invalid request type', ['error' => $e->getMessage()]);
            return $this->errorResponse('Неверный тип запроса: ' . $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('Unexpected error in LLMController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Внутренняя ошибка сервера', 500);
        }
    }
    
    /**
     * Получить дефолтную температуру для типа запроса
     */
    private function getDefaultTemperature(RequestType $type): float
    {
        return match($type) {
            RequestType::GAME_POST => 0.7,      // Более креативные ответы
            RequestType::CHAPTER_END => 0.5,    // Умеренная креативность
            RequestType::GENERATE_PLOTPOINTS => 0.8, // Высокая креативность для сюжета
        };
    }
    
    /**
     * Получить дефолтное количество токенов для типа запроса
     */
    private function getDefaultMaxTokens(RequestType $type): int
    {
        return match($type) {
            RequestType::GAME_POST => 800,
            RequestType::CHAPTER_END => 1500,
            RequestType::GENERATE_PLOTPOINTS => 2000,
        };
    }
    
    /**
     * Стандартизированный ответ с ошибкой
     */
    private function errorResponse(string $message, int $statusCode = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message
        ], $statusCode);
    }
}