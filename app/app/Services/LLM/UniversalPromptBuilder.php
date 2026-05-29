<?php

namespace App\Services\LLM;

use App\Enums\RequestType;

class UniversalPromptBuilder
{
    /**
     * Построить системный промпт (instructions)
     */
    public function buildInstructions(RequestType $type): string
    {
        $baseInstruction = "Ты - мастер игры в текстовом RPG. Твоя задача - генерировать контент строго в JSON формате. Не добавляй никакого текста кроме JSON. Используй русский язык.\n\n";
        
        return $baseInstruction . $this->getSpecificInstructions($type);
    }
    
    /**
     * Построить пользовательский промпт (input)
     */
    public function buildInput(RequestType $type, array $data): string
    {
        return match($type) {
            RequestType::GAME_POST => $this->buildGamePostInput($data),
            RequestType::CHAPTER_END => $this->buildChapterEndInput($data),
            RequestType::GENERATE_PLOTPOINTS => $this->buildPlotpointsInput($data),
        };
    }
    
    private function getSpecificInstructions(RequestType $type): string
    {
        return match($type) {
            RequestType::GAME_POST => $this->getGamePostInstructions(),
            RequestType::CHAPTER_END => $this->getChapterEndInstructions(),
            RequestType::GENERATE_PLOTPOINTS => $this->getPlotpointsInstructions(),
        };
    }
    
    private function getGamePostInstructions(): string
    {
        return <<<'INSTRUCTIONS'
Ты должен сгенерировать пост от лица игрового мира на основе действия игрока.

Формат ответа (строго JSON):
{
    "ai_post": "текст поста от 2-4 предложений с описанием результата действия",
    "updated_characters": [
        {
            "id": 1,
            "attitude": "изменённое_отношение",
            "memory": "что персонаж запомнил"
        }
    ],
    "player_memory_update": "ключевая информация, которую должен запомнить игрок",
    "chapter_break": false,
    "chapter_break_reason": "если chapter_break=true, укажи причину"
}

Правила:
- ai_post должен быть увлекательным и реактивным на действие игрока
- Если действие игрока логически завершает главу, установи chapter_break = true
- Обновляй отношение персонажей (дружелюбное, нейтральное, враждебное, подозрительное и т.д.)
- Память персонажа должна отражать последнее важное событие с его участием
INSTRUCTIONS;
    }
    
    private function getChapterEndInstructions(): string
    {
        return <<<'INSTRUCTIONS'
Ты должен подвести итог завершённой главы игры.

Формат ответа (строго JSON):
{
    "chapter_result": "развёрнутый пересказ событий главы с учётом действий игрока (3-5 предложений)",
    "player_memory_update": "какую важную информацию игрок должен вынести из этой главы",
    "global_plot_progression": "как продвинулся глобальный сюжет"
}

Правила:
- Сохраняй драматизм и важность событий
- Выдели ключевые решения игрока
- Подготовь почву для следующей главы
INSTRUCTIONS;
    }
    
    private function getPlotpointsInstructions(): string
    {
        return <<<'INSTRUCTIONS'
Ты должен сгенерировать сюжетные точки (plotpoints) для новой главы.

Формат ответа (строго JSON):
{
    "plotpoints": [
        {
            "synopsis": "краткое описание сюжетной точки (1 предложение)",
            "expected_challenge": "ожидаемое испытание для игрока"
        }
    ]
}

Правила:
- Сгенерируй ровно столько plotpoints, сколько запрошено
- Каждая следующая точка логически вытекает из предыдущей
- Сохраняй связь с глобальным сюжетом
- Создавай разнообразные ситуации (битвы, диалоги, головоломки)
INSTRUCTIONS;
    }
    
    private function buildGamePostInput(array $data): string
    {
        // Безопасно получаем значения с дефолтами ДО вставки в строку
        $playerMemory = $data['player_memory'] ?? 'Нет данных';
        $lastAiPost = $data['last_ai_post'] ?? 'Начало истории';
        $lastPlayerPost = $data['last_player_post'] ?? 'Игрок осматривается';
        $previousResult = isset($data['previous_result']) ? $data['previous_result'] : 'Начало главы';
        
        $charactersText = '';
        if (!empty($data['characters']) && is_array($data['characters'])) {
            foreach ($data['characters'] as $char) {
                $charName = $char['name'] ?? 'Неизвестный';
                $charAttitude = $char['attitude'] ?? 'нейтральное';
                $charMemory = $char['memory'] ?? 'Ничего особенного';
                $charactersText .= "- {$charName} (отношение: {$charAttitude}, помнит: {$charMemory})\n";
            }
        } else {
            $charactersText = "Нет активных персонажей\n";
        }
        
        return <<<INPUT
Контекст игры:

Память игрока: {$playerMemory}

Активные персонажи:
{$charactersText}
Последний пост ИИ: {$lastAiPost}

Действие игрока: {$lastPlayerPost}

Предыдущий итог: {$previousResult}

Сгенерируй ответ в указанном JSON формате.
INPUT;
    }
    
    private function buildChapterEndInput(array $data): string
    {
        // Безопасно получаем значения
        $chapterTitle = $data['chapter_title'] ?? 'Без названия';
        $chapterSummary = $data['chapter_summary'] ?? 'События главы';
        
        $plotpointsText = '';
        if (!empty($data['plotpoints']) && is_array($data['plotpoints'])) {
            foreach ($data['plotpoints'] as $index => $pp) {
                $ppNumber = $index + 1;
                $aiPost = $pp['ai_post'] ?? 'Нет данных';
                $playerPost = $pp['player_post'] ?? 'Нет данных';
                $result = $pp['result'] ?? 'Нет итога';
                $plotpointsText .= "\nТочка {$ppNumber}:\n";
                $plotpointsText .= "- Пост ИИ: {$aiPost}\n";
                $plotpointsText .= "- Пост игрока: {$playerPost}\n";
                $plotpointsText .= "- Итог: {$result}\n";
            }
        } else {
            $plotpointsText = "\nНет данных о событиях главы\n";
        }
        
        $previousResults = '';
        if (!empty($data['previous_chapters_results']) && is_array($data['previous_chapters_results'])) {
            $previousResults = "\nИтоги предыдущих глав:\n";
            foreach ($data['previous_chapters_results'] as $i => $result) {
                $chapterNum = $i + 1;
                $previousResults .= "Глава {$chapterNum}: {$result}\n";
            }
        }
        
        return <<<INPUT
Завершённая глава:

Название главы: {$chapterTitle}

Краткое содержание главы: {$chapterSummary}

События главы по шагам:{$plotpointsText}
{$previousResults}
Подведи итог этой главы в указанном JSON формате.
INPUT;
    }
    
    private function buildPlotpointsInput(array $data): string
    {
        // Безопасно получаем значения
        $globalPlot = $data['global_plot'] ?? 'Стандартное приключение';
        $chapterText = $data['chapter_text'] ?? 'Новая глава';
        $previousChapterResult = $data['previous_chapter_result'] ?? 'Начало приключения';
        $count = isset($data['count']) ? (int)$data['count'] : 5;
        
        return <<<INPUT
Глобальный сюжет: {$globalPlot}

Описание новой главы: {$chapterText}

Итог предыдущей главы: {$previousChapterResult}

Количество plotpoints: {$count}

Сгенерируй {$count} сюжетных точек в указанном JSON формате.
INPUT;
    }
}