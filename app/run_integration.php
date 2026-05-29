<?php
// run_integration.php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\LLM\YandexLLMClient;
use App\Services\LLM\UniversalPromptBuilder;
use App\Enums\RequestType;

echo "\n========================================\n";
echo "Интеграционные тесты Yandex API\n";
echo "========================================\n\n";

// Проверяем наличие ключей
if (!env('YANDEX_API_KEY') || !env('YANDEX_FOLDER_ID')) {
    die("❌ Ошибка: YANDEX_API_KEY или YANDEX_FOLDER_ID не заданы в .env\n");
}

$client = new YandexLLMClient();
$builder = new UniversalPromptBuilder();

// Тест 1: Game Post
echo "📝 Тест 1: game_post запрос\n";
echo "----------------------------------------\n";

$instructions = $builder->buildInstructions(RequestType::GAME_POST);
$input = $builder->buildInput(RequestType::GAME_POST, [
    'request_type' => 'game_post',
    'player_memory' => 'Игрок стоит перед воротами замка',
    'characters' => [
        [
            'id' => 1,
            'name' => 'Стражник',
            'attitude' => 'нейтральное',
            'memory' => 'Стоит на посту'
        ]
    ],
    'last_ai_post' => 'Перед вами массивные дубовые ворота замка',
    'last_player_post' => 'Я стучу в ворота три раза',
    'previous_result' => 'Игрок подошёл к воротам'
]);

$result = $client->ask($instructions, $input, 0.7, 300);

if ($result['success']) {
    echo "✅ Успех!\n";
    echo "\n📥 Полученный JSON от ИИ:\n";
    echo str_repeat('-', 40) . "\n";
    
    // Парсим JSON для красивого вывода
    $decoded = json_decode($result['content'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Проверяем структуру
        if (isset($decoded['ai_post'])) {
            echo "\n\n📝 Пост ИИ:\n";
            echo $decoded['ai_post'] . "\n";
        }
    } else {
        echo $result['content'] . "\n";
    }
    echo str_repeat('-', 40) . "\n\n";
} else {
    echo "❌ Ошибка: " . $result['error'] . "\n\n";
    if (isset($result['debug_response'])) {
        echo "Отладочная информация:\n";
        print_r($result['debug_response']);
    }
}

// Тест 2: Chapter End
echo "📝 Тест 2: chapter_end запрос\n";
echo "----------------------------------------\n";

$instructions = $builder->buildInstructions(RequestType::CHAPTER_END);
$input = $builder->buildInput(RequestType::CHAPTER_END, [
    'request_type' => 'chapter_end',
    'chapter_title' => 'Врата замка',
    'chapter_summary' => 'Игрок пытался попасть в замок',
    'plotpoints' => [
        [
            'ai_post' => 'Стражник не пускает',
            'player_post' => 'Показываю печать',
            'result' => 'Вход разрешён'
        ]
    ],
    'previous_chapters_results' => ['Игрок прибыл в город']
]);

$result = $client->ask($instructions, $input, 0.5, 500);

if ($result['success']) {
    echo "✅ Успех!\n";
    echo "\n📥 Полученный JSON от ИИ:\n";
    echo str_repeat('-', 40) . "\n";
    
    $decoded = json_decode($result['content'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (isset($decoded['chapter_result'])) {
            echo "\n\n📝 Итог главы:\n";
            echo $decoded['chapter_result'] . "\n";
        }
    } else {
        echo $result['content'] . "\n";
    }
    echo str_repeat('-', 40) . "\n\n";
} else {
    echo "❌ Ошибка: " . $result['error'] . "\n\n";
}

echo "========================================\n";
echo "Тестирование завершено\n";
echo "========================================\n";