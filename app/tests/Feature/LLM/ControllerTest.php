<?php
// tests/Feature/LLM/ControllerTest.php

namespace Tests\Feature\LLM;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class ControllerTest extends TestCase
{
    public function test_requires_request_type(): void
    {
        $response = $this->postJson('/api/llm/ask', []);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['request_type']);
    }

    public function test_game_post_success(): void
    {
        Http::fake([
            'ai.api.cloud.yandex.net/*' => Http::response([
                'output' => ['message' => ['content' => '{"ai_post": "OK", "updated_characters": [], "player_memory_update": "test", "chapter_break": false}']]
            ], 200)
        ]);
        
        $response = $this->postJson('/api/llm/ask', [
            'request_type' => 'game_post',
            'player_memory' => 'Тест',
            'characters' => [['id' => 1, 'name' => 'Тест', 'attitude' => 'нейтральное', 'memory' => 'Тест']],
            'last_ai_post' => 'Тест',
            'last_player_post' => 'Тест'
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_chapter_end_success(): void
    {
        Http::fake([
            'ai.api.cloud.yandex.net/*' => Http::response([
                'output' => ['message' => ['content' => '{"chapter_result": "Глава завершена", "player_memory_update": "Новая память", "global_plot_progression": "Продвижение"}']]
            ], 200)
        ]);
        
        // Исправлено: plotpoints теперь имеет правильную структуру
        $response = $this->postJson('/api/llm/ask', [
            'request_type' => 'chapter_end',
            'chapter_title' => 'Тестовая глава',
            'chapter_summary' => 'Краткое содержание главы',
            'plotpoints' => [
                [
                    'ai_post' => 'Пост от ИИ',
                    'player_post' => 'Действие игрока',
                    'result' => 'Результат действия'
                ]
            ],
            'previous_chapters_results' => ['Глава 1: Начало']
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'response_to_player',
                'player_memory',
                'characters',
                'plotpoints',
                'chapter_result',
                'global_plot_progression'
            ]
        ]);
    }

    public function test_generate_plotpoints_success(): void
    {
        Http::fake([
            'ai.api.cloud.yandex.net/*' => Http::response([
                'output' => ['message' => ['content' => '{"plotpoints": [{"synopsis": "Точка 1", "expected_challenge": "Бой"}]}']]
            ], 200)
        ]);
        
        $response = $this->postJson('/api/llm/ask', [
            'request_type' => 'generate_plotpoints',
            'global_plot' => 'Спасти королевство',
            'chapter_text' => 'Найти древний артефакт',
            'previous_chapter_result' => 'Герой получил карту',
            'count' => 3
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'response_to_player',
                'player_memory',
                'characters',
                'plotpoints',
                'generated_count'
            ]
        ]);
    }

    // Дополнительный тест: проверка валидации для chapter_end
    public function test_chapter_end_requires_plotpoints_with_correct_structure(): void
    {
        $response = $this->postJson('/api/llm/ask', [
            'request_type' => 'chapter_end',
            'chapter_title' => 'Тест',
            'chapter_summary' => 'Тест',
            'plotpoints' => []  // Пустой массив - не проходит валидацию
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['plotpoints']);
    }

    // Дополнительный тест: проверка обязательных полей для game_post
    public function test_game_post_requires_required_fields(): void
    {
        $response = $this->postJson('/api/llm/ask', [
            'request_type' => 'game_post'
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'player_memory',
            'characters',
            'last_ai_post',
            'last_player_post'
        ]);
    }

    // Дополнительный тест: проверка обязательных полей для generate_plotpoints
    public function test_generate_plotpoints_requires_required_fields(): void
    {
        $response = $this->postJson('/api/llm/ask', [
            'request_type' => 'generate_plotpoints'
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'chapter_text',
            'global_plot',
            'previous_chapter_result'
        ]);
    }
}