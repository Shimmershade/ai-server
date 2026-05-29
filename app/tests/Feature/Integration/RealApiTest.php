<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Services\LLM\YandexLLMClient;
use App\Services\LLM\UniversalPromptBuilder;
use App\Enums\RequestType;
use Illuminate\Support\Facades\Log;

/**
 * Интеграционные тесты с реальным Yandex API
 * 
 * Запуск: php artisan test --filter=RealApiTest
 * Или:   php artisan test tests/Feature/Integration/RealApiTest.php
 */
class RealApiTest extends TestCase
{
    private YandexLLMClient $client;
    private UniversalPromptBuilder $builder;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Автоматически пропускаем тест, если не указан специальный флаг
        if (!env('RUN_REAL_API_TESTS')) {
            $this->markTestSkipped(
                'Real API tests are skipped. ' .
                'Set RUN_REAL_API_TESTS=true in .env to run them.'
            );
        }
        
        // Проверяем наличие API ключей
        if (!env('YANDEX_API_KEY') || !env('YANDEX_FOLDER_ID')) {
            $this->markTestSkipped(
                'Yandex API keys not configured. ' .
                'Please set YANDEX_API_KEY and YANDEX_FOLDER_ID in .env'
            );
        }
        
        $this->client = new YandexLLMClient();
        $this->builder = new UniversalPromptBuilder();
        
        // Логируем начало теста
        Log::info('Running real API test', ['test' => static::class]);
    }
    
    /**
     * @test
     * @group real-api
     * @group integration
     */
    public function game_post_request_returns_valid_json(): void
    {
        echo "\n\n[Integration] Тест game_post с реальным API\n";
        echo str_repeat('-', 60) . "\n";
        
        $instructions = $this->builder->buildInstructions(RequestType::GAME_POST);
        $input = $this->builder->buildInput(RequestType::GAME_POST, [
            'request_type' => 'game_post',
            'player_memory' => 'Игрок стоит перед воротами замка',
            'characters' => [
                [
                    'id' => 1,
                    'name' => 'Старый стражник',
                    'attitude' => 'нейтральное',
                    'memory' => 'Стоит на посту уже много лет'
                ]
            ],
            'last_ai_post' => 'Перед вами массивные дубовые ворота замка',
            'last_player_post' => 'Я стучу в ворота три раза',
            'previous_result' => 'Игрок подошёл к воротам'
        ]);
        
        $result = $this->client->ask($instructions, $input, 0.7, 300);
        
        $this->assertTrue($result['success'], 'API request failed: ' . ($result['error'] ?? 'Unknown error'));
        
        $content = $result['content'];
        $this->assertIsString($content);
        
        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded, 'Response is not valid JSON: ' . substr($content, 0, 200));
        
        $this->assertArrayHasKey('ai_post', $decoded, 'Missing ai_post in response');
        $this->assertArrayHasKey('updated_characters', $decoded, 'Missing updated_characters');
        $this->assertArrayHasKey('player_memory_update', $decoded, 'Missing player_memory_update');
        $this->assertArrayHasKey('chapter_break', $decoded, 'Missing chapter_break');
        
        $this->assertIsString($decoded['ai_post']);
        $this->assertIsArray($decoded['updated_characters']);
        $this->assertIsString($decoded['player_memory_update']);
        $this->assertIsBool($decoded['chapter_break']);
        
        echo "\nТест пройден!\n";
        echo "\nПост ИИ:\n";
        echo str_repeat('~', 40) . "\n";
        echo $decoded['ai_post'] . "\n";
        echo str_repeat('~', 40) . "\n";
        
        echo "\nОбновления персонажей:\n";
        foreach ($decoded['updated_characters'] as $char) {
            echo "  - {$char['id']}: отношение = {$char['attitude']}, память = {$char['memory']}\n";
        }
        
        echo "\nПамять игрока: " . $decoded['player_memory_update'] . "\n";
        echo "\nЗавершение главы: " . ($decoded['chapter_break'] ? 'Да' : 'Нет') . "\n";
    }
    
    /**
     * @test
     * @group real-api
     * @group integration
     */
    public function chapter_end_request_returns_valid_json(): void
    {
        echo "\n\n[Integration] Тест chapter_end с реальным API\n";
        echo str_repeat('-', 60) . "\n";
        
        $instructions = $this->builder->buildInstructions(RequestType::CHAPTER_END);
        $input = $this->builder->buildInput(RequestType::CHAPTER_END, [
            'request_type' => 'chapter_end',
            'chapter_title' => 'Врата замка',
            'chapter_summary' => 'Игрок пытался попасть в замок через главные ворота',
            'plotpoints' => [
                [
                    'ai_post' => 'Стражник отказывается открывать ворота',
                    'player_post' => 'Я показываю королевскую печать',
                    'result' => 'Стражник узнаёт печать и открывает ворота'
                ],
                [
                    'ai_post' => 'Внутри замка вас встречает дворецкий',
                    'player_post' => 'Я спрашиваю, где король',
                    'result' => 'Дворецкий провожает вас в тронный зал'
                ]
            ],
            'previous_chapters_results' => [
                'Игрок прибыл в город',
                'Игрок нашёл дорогу к замку'
            ]
        ]);
        
        $result = $this->client->ask($instructions, $input, 0.5, 500);
        
        $this->assertTrue($result['success'], 'API request failed: ' . ($result['error'] ?? 'Unknown error'));
        
        $content = $result['content'];
        $this->assertIsString($content);
        
        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded, 'Response is not valid JSON: ' . substr($content, 0, 200));
        
        $this->assertArrayHasKey('chapter_result', $decoded, 'Missing chapter_result in response');
        $this->assertArrayHasKey('player_memory_update', $decoded, 'Missing player_memory_update');
        $this->assertArrayHasKey('global_plot_progression', $decoded, 'Missing global_plot_progression');
        
        $this->assertIsString($decoded['chapter_result']);
        $this->assertIsString($decoded['player_memory_update']);
        $this->assertIsString($decoded['global_plot_progression']);
        
        echo "\nТест пройден!\n";
        echo "\nИтог главы:\n";
        echo str_repeat('~', 40) . "\n";
        echo $decoded['chapter_result'] . "\n";
        echo str_repeat('~', 40) . "\n";
        
        echo "\nПамять игрока: " . $decoded['player_memory_update'] . "\n";
        echo "\nПрогресс сюжета: " . $decoded['global_plot_progression'] . "\n";
    }
    
    /**
     * @test
     * @group real-api
     * @group integration
     */
    public function generate_plotpoints_request_returns_valid_json(): void
    {
        echo "\n\n[Integration] Тест generate_plotpoints с реальным API\n";
        echo str_repeat('-', 60) . "\n";
        
        $instructions = $this->builder->buildInstructions(RequestType::GENERATE_PLOTPOINTS);
        $input = $this->builder->buildInput(RequestType::GENERATE_PLOTPOINTS, [
            'request_type' => 'generate_plotpoints',
            'global_plot' => 'Спасти королевство от древнего дракона',
            'chapter_text' => 'Игрок должен найти тайный проход в горы, где спит дракон',
            'previous_chapter_result' => 'Игрок получил карту от старого мага',
            'count' => 3
        ]);
        
        $result = $this->client->ask($instructions, $input, 0.8, 1000);
        
        $this->assertTrue($result['success'], 'API request failed: ' . ($result['error'] ?? 'Unknown error'));
        
        $content = $result['content'];
        $this->assertIsString($content);
        
        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded, 'Response is not valid JSON: ' . substr($content, 0, 200));
        
        $this->assertArrayHasKey('plotpoints', $decoded, 'Missing plotpoints in response');
        $this->assertIsArray($decoded['plotpoints']);
        $this->assertGreaterThan(0, count($decoded['plotpoints']), 'Plotpoints array is empty');
        
        foreach ($decoded['plotpoints'] as $index => $point) {
            $this->assertArrayHasKey('synopsis', $point, "Missing synopsis in plotpoint {$index}");
            $this->assertArrayHasKey('expected_challenge', $point, "Missing expected_challenge in plotpoint {$index}");
            $this->assertIsString($point['synopsis']);
            $this->assertIsString($point['expected_challenge']);
        }
        
        echo "\nТест пройден!\n";
        echo "\nСгенерированные сюжетные точки:\n";
        echo str_repeat('~', 40) . "\n";
        
        foreach ($decoded['plotpoints'] as $index => $point) {
            echo ($index + 1) . ". " . $point['synopsis'] . "\n";
            echo "   Испытание: " . $point['expected_challenge'] . "\n\n";
        }
        echo str_repeat('~', 40) . "\n";
    }
}