<?php
// tests/Unit/LLM/ResponseParserTest.php

namespace Tests\Unit\LLM;

use Tests\TestCase;
use App\Services\LLM\UniversalResponseParser;
use App\Enums\RequestType;

class ResponseParserTest extends TestCase
{
    private UniversalResponseParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new UniversalResponseParser();
    }

    public function test_parse_game_post_response(): void
    {
        $llmResponse = '{"ai_post": "Привет!", "updated_characters": [], "player_memory_update": "Память", "chapter_break": false}';
        $originalData = ['characters' => []];
        
        $result = $this->parser->parse(RequestType::GAME_POST, $llmResponse, $originalData);
        
        $this->assertEquals('Привет!', $result['response_to_player']);
        $this->assertEquals('Память', $result['player_memory']);
    }

    public function test_parse_chapter_end_response(): void
    {
        $llmResponse = '{"chapter_result": "Глава завершена", "player_memory_update": "Новая память"}';
        
        $result = $this->parser->parse(RequestType::CHAPTER_END, $llmResponse, []);
        
        $this->assertEquals('Глава завершена', $result['response_to_player']);
    }

    public function test_parse_plotpoints_response(): void
    {
        $llmResponse = '{"plotpoints": [{"synopsis": "Точка 1", "expected_challenge": "Бой"}]}';
        
        $result = $this->parser->parse(RequestType::GENERATE_PLOTPOINTS, $llmResponse, []);
        
        $this->assertCount(1, $result['plotpoints']);
        $this->assertEquals('Точка 1', $result['plotpoints'][0]['synopsis']);
    }

    public function test_handle_malformed_json(): void
    {
        $llmResponse = 'Это не JSON простой текст';
        $originalData = ['characters' => []];
        
        $result = $this->parser->parse(RequestType::GAME_POST, $llmResponse, $originalData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('response_to_player', $result);
    }
}