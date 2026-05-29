<?php
// app/Services/LLM/UniversalResponseParser.php

namespace App\Services\LLM;

use App\Enums\RequestType;
use Illuminate\Support\Facades\Log;

class UniversalResponseParser
{
    /**
     * Парсинг ответа ИИ в зависимости от типа запроса
     */
    public function parse(RequestType $type, string $llmResponse, array $originalData): array
    {
        // Очищаем ответ от возможных markdown-обёрток
        $cleanResponse = $this->cleanResponse($llmResponse);
        
        // Пытаемся декодировать JSON
        $decoded = json_decode($cleanResponse, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse LLM response', [
                'error' => json_last_error_msg(),
                'response' => $llmResponse
            ]);
            
            // fallback при ошибке парсинга
            return $this->getFallbackResponse($type, $originalData);
        }
        
        return match($type) {
            RequestType::GAME_POST => $this->parseGamePost($decoded, $originalData),
            RequestType::CHAPTER_END => $this->parseChapterEnd($decoded),
            RequestType::GENERATE_PLOTPOINTS => $this->parsePlotpoints($decoded),
        };
    }
    
    private function parseGamePost(array $decoded, array $originalData): array
    {
        // Обновляем персонажей
        $updatedCharacters = $originalData['characters'] ?? [];
        
        if (isset($decoded['updated_characters']) && is_array($decoded['updated_characters'])) {
            foreach ($updatedCharacters as &$character) {
                foreach ($decoded['updated_characters'] as $update) {
                    if (($update['id'] ?? null) === ($character['id'] ?? null)) {
                        $character['attitude'] = $update['attitude'] ?? $character['attitude'];
                        $character['memory'] = $update['memory'] ?? $character['memory'];
                        break;
                    }
                }
            }
        }
        
        return [
            'response_to_player' => $decoded['ai_post'] ?? 'События развиваются...',
            'player_memory' => $decoded['player_memory_update'] ?? $decoded['ai_post'] ?? '',
            'characters' => $updatedCharacters,
            'plotpoints' => [], // БД-сервис сам обновит текущий plotpoint
            'chapter_break' => $decoded['chapter_break'] ?? false,
            'chapter_break_reason' => $decoded['chapter_break_reason'] ?? null,
        ];
    }
    
    private function parseChapterEnd(array $decoded): array
    {
        return [
            'response_to_player' => $decoded['chapter_result'] ?? 'Глава завершена.',
            'player_memory' => $decoded['player_memory_update'] ?? $decoded['chapter_result'] ?? '',
            'characters' => [], // не меняем персонажей при завершении главы
            'plotpoints' => [],
            'chapter_result' => $decoded['chapter_result'] ?? '',
            'global_plot_progression' => $decoded['global_plot_progression'] ?? '',
        ];
    }
    
    private function parsePlotpoints(array $decoded): array
    {
        $plotpoints = [];
        
        if (isset($decoded['plotpoints']) && is_array($decoded['plotpoints'])) {
            foreach ($decoded['plotpoints'] as $pp) {
                $plotpoints[] = [
                    'synopsis' => $pp['synopsis'] ?? $pp['description'] ?? '',
                    'expected_challenge' => $pp['expected_challenge'] ?? '',
                    'status' => 'pending',
                ];
            }
        }
        
        return [
            'response_to_player' => "Сгенерировано " . count($plotpoints) . " сюжетных точек.",
            'player_memory' => '',
            'characters' => [],
            'plotpoints' => $plotpoints,
            'generated_count' => count($plotpoints),
        ];
    }
    
    private function cleanResponse(string $response): string
    {
        // Убираем markdown-блоки ```json ... ```
        $response = preg_replace('/```json\s*|\s*```/', '', $response);
        
        // Убираем возможный текст до первого {
        if (($start = strpos($response, '{')) !== false) {
            $response = substr($response, $start);
        }
        
        // Убираем текст после последнего }
        if (($end = strrpos($response, '}')) !== false) {
            $response = substr($response, 0, $end + 1);
        }
        
        return trim($response);
    }
    
    private function getFallbackResponse(RequestType $type, array $originalData): array
    {
        return match($type) {
            RequestType::GAME_POST => [
                'response_to_player' => 'ИИ временно недоступен. Пожалуйста, попробуйте ещё раз.',
                'player_memory' => $originalData['player_memory'] ?? '',
                'characters' => $originalData['characters'] ?? [],
                'plotpoints' => [],
                'chapter_break' => false,
            ],
            RequestType::CHAPTER_END => [
                'response_to_player' => 'Глава завершена. Итог будет подведён позже.',
                'player_memory' => '',
                'characters' => [],
                'plotpoints' => [],
                'chapter_result' => 'Итог главы временно недоступен.',
            ],
            RequestType::GENERATE_PLOTPOINTS => [
                'response_to_player' => 'Генерация сюжета временно недоступна.',
                'player_memory' => '',
                'characters' => [],
                'plotpoints' => [],
                'generated_count' => 0,
            ],
        };
    }
}