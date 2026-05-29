<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LLM\YandexLLMClient;
use App\Services\LLM\UniversalPromptBuilder;
use App\Services\LLM\UniversalResponseParser;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(YandexLLMClient::class);
        $this->app->singleton(UniversalPromptBuilder::class);
        $this->app->singleton(UniversalResponseParser::class);
    }
}