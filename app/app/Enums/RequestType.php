<?php
namespace App\Enums;

enum RequestType: string
{
    case GAME_POST = 'game_post';
    case CHAPTER_END = 'chapter_end';
    case GENERATE_PLOTPOINTS = 'generate_plotpoints';
    
    public function getDescription(): string
    {
        return match($this) {
            self::GAME_POST => 'Генерация поста ИИ в ответ на действие игрока',
            self::CHAPTER_END => 'Подведение итогов главы',
            self::GENERATE_PLOTPOINTS => 'Генерация сюжетных точек для новой главы',
        };
    }
}