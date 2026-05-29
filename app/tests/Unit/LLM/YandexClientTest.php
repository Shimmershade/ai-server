<?php
// tests/Unit/LLM/YandexClientTest.php

namespace Tests\Unit\LLM;

use Tests\TestCase;
use App\Services\LLM\YandexLLMClient;
use Illuminate\Support\Facades\Http;

class YandexClientTest extends TestCase
{
    private YandexLLMClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new YandexLLMClient();
    }

    public function test_client_initialization(): void
    {
        $this->assertInstanceOf(YandexLLMClient::class, $this->client);
    }

    public function test_ask_returns_error_on_failure(): void
    {
        Http::fake([
            'ai.api.cloud.yandex.net/*' => Http::response([], 500)
        ]);
        
        $result = $this->client->ask('test', 'test');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
}