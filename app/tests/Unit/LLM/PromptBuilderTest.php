<?php

namespace Tests\Unit\LLM;

//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Services\LLM\UniversalPromptBuilder;
use App\Enums\RequestType;

class PromptBuilderTest extends TestCase
{
    private UniversalPromptBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new UniversalPromptBuilder();
    }

    public function test_build_instructions_for_game_post(): void
    {
        $instructions = $this->builder->buildInstructions(RequestType::GAME_POST);
        
        $this->assertIsString($instructions);
        $this->assertStringContainsString('JSON', $instructions);
    }

    public function test_build_input_for_game_post(): void
    {
        $data = [
            'request_type' => 'game_post',
            'player_memory' => 'Игрок ищет замок',
            'characters' => [
                ['id' => 1, 'name' => 'Алиса', 'attitude' => 'нейтральное', 'memory' => 'Встретила игрока']
            ],
            'last_ai_post' => 'Вы видите стражника',
            'last_player_post' => 'Я подхожу'
        ];
        
        $input = $this->builder->buildInput(RequestType::GAME_POST, $data);
        
        $this->assertIsString($input);
        $this->assertStringContainsString('Игрок ищет замок', $input);
    }

    public function test_build_input_for_chapter_end(): void
    {
        $data = [
            'request_type' => 'chapter_end',
            'chapter_title' => 'Тест',
            'chapter_summary' => 'Тест',
            'plotpoints' => []
        ];
        
        $input = $this->builder->buildInput(RequestType::CHAPTER_END, $data);
        
        $this->assertIsString($input);
        $this->assertStringContainsString('Тест', $input);
    }

    public function test_build_input_for_plotpoints(): void
    {
        $data = [
            'request_type' => 'generate_plotpoints',
            'global_plot' => 'Спасти мир',
            'chapter_text' => 'Новая глава',
            'previous_chapter_result' => 'Предыдущий итог',
            'count' => 3
        ];
        
        $input = $this->builder->buildInput(RequestType::GENERATE_PLOTPOINTS, $data);
        
        $this->assertIsString($input);
        $this->assertStringContainsString('Спасти мир', $input);
    }
}