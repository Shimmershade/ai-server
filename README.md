# ИИ-сервис для генерации текстовых квестов

Сервис для генерации сюжета в текстовой RPG-игре с использованием Yandex GPT API. Создан как часть командного проекта по микросервисной архитектуре.
Вторая часть проекта: [контекст-сервис](https://github.com/veronica061/context-server)

## О проекте

Проект представляет собой ИИ-сервис, который:
- Генерирует посты от лица игрового мира в ответ на действия игрока
- Управляет сюжетными точками (плотпоинтами)
- Отслеживает состояние персонажей и их отношения к игроку
- Подводит итоги глав

Референсы: Character AI, Status AI

Игровой процесс:
1. Игрок описывает действие через ТГ-бота (контекст-сервис)
2. Контекст-сервис отправляет запрос в ИИ-сервис
3. ИИ-сервис генерирует ответ через Yandex GPT
4. Ответ возвращается контекст-сервису для сохранения и отображения

## Стек технологий

| Компонент | Технология |
|-----------|-----------|
| Backend | PHP 8.3, Laravel 13 |
| ИИ-интеграция | Yandex GPT API (DeepSeek V32) |
| HTTP клиент | Laravel HTTP Client |
| Контейнеризация | Docker, Docker Compose |
| Тестирование | PHPUnit |
| CI/CD | GitHub Actions |

## Установка и запуск

### Через Docker (рекомендуется)

```bash
# 1. Клонировать репозиторий
git clone https://github.com/Shimmershade/ai-server.git
git switch develop
cd ai-server

# 2. Собрать образ
docker build -t llm-service .

# 3. Запустить контейнер
docker run -d \
  -p 8000:8000 \
  -e YANDEX_API_KEY="ваш_ключ" \
  -e YANDEX_FOLDER_ID="ваш_folder_id" \
  --name llm-service \
  llm-service

# 4. Проверить работу
curl http://localhost:8000/api/health
```

### Локально (без Docker)

```bash
# 1. Установить зависимости
composer install

# 2. Создать .env и добавить ключи
cp .env.example .env
# Отредактировать .env: YANDEX_API_KEY, YANDEX_FOLDER_ID

# 3. Запустить сервер
php artisan serve --host=0.0.0.0 --port=8000
```

## Тестирование

### Запуск всех тестов (исключая интеграционные)

```bash
# В запущенном контейнере
docker exec llm-service php artisan test --exclude-group=integration

# Локально
php artisan test --exclude-group=integration
```

### Запуск конкретных тестов

```bash
# Unit тесты
docker exec llm-service php artisan test --testsuite=Unit

# Feature тесты
docker exec llm-service php artisan test --testsuite=Feature

# Конкретный файл
docker exec llm-service php artisan test tests/Unit/LLM/PromptBuilderTest.php

# Конкретный тест по имени
docker exec llm-service php artisan test --filter=test_build_input_for_game_post
```

### Интеграционные тесты 

```bash
# Включаются только при наличии ENV переменной
RUN_REAL_API_TESTS=true docker exec llm-service php artisan test --group=integration

# Или
RUN_REAL_API_TESTS=true php artisan test tests/Feature/Integration/
```

## API Endpoints

### POST /api/llm/ask

Основной эндпоинт для взаимодействия с ИИ.

Типы запросов:

#### 1. game_post — генерация поста в ответ на действие игрока

```json
{
    "request_type": "game_post",
    "player_memory": "Игрок стоит перед воротами замка",
    "characters": [
        {
            "id": 1,
            "name": "Стражник",
            "attitude": "нейтральное",
            "memory": "Стоит на посту"
        }
    ],
    "last_ai_post": "Перед вами массивные дубовые ворота",
    "last_player_post": "Я стучу в ворота три раза"
}
```

#### 2. chapter_end — подведение итогов главы

```json
{
    "request_type": "chapter_end",
    "chapter_title": "Врата замка",
    "chapter_summary": "Игрок пытался попасть в замок",
    "plotpoints": [
        {
            "ai_post": "Стражник не пускает",
            "player_post": "Показываю печать",
            "result": "Вход разрешён"
        }
    ]
}
```

#### 3. generate_plotpoints — генерация сюжетных точек

```json
{
    "request_type": "generate_plotpoints",
    "global_plot": "Спасти королевство",
    "chapter_text": "Найти тайный проход в горы",
    "previous_chapter_result": "Игрок получил карту",
    "count": 5
}
```

### GET /api/health

Проверка работоспособности сервиса.

```json
{
    "status": "ok",
    "timestamp": "2025-05-29T12:00:00+00:00",
    "service": "llm-service",
    "version": "1.0.0"
}
```

## Команды для работы с Docker

```bash
# Собрать образ
docker build -t llm-service .

# Запустить контейнер
docker run -d -p 8000:8000 --name llm-service llm-service

# Остановить контейнер
docker stop llm-service

# Удалить контейнер
docker rm llm-service

# Перезапустить с новой сборкой
docker rm -f llm-service && docker build -t llm-service . && docker run -d -p 8000:8000 --name llm-service llm-service

# Посмотреть логи
docker logs llm-service -f

# Зайти внутрь контейнера
docker exec -it llm-service sh

# Запустить тесты
docker exec llm-service php artisan test
```

## Переменные окружения

| Переменная | Описание | Обязательно |
|-----------|----------|-------------|
| YANDEX_API_KEY | API ключ Yandex | Да |
| YANDEX_FOLDER_ID | ID каталога в Yandex Cloud | Да |
| RUN_REAL_API_TESTS | Включить интеграционные тесты (true/false) | Нет |

Пример .env:
```env
APP_ENV=production
YANDEX_API_KEY=ваш_ключ
YANDEX_FOLDER_ID=ваш_folder_id
RUN_REAL_API_TESTS=false
```

## Взаимодействие с контекст-сервисом

Формат ответа ИИ-сервиса:

```json
{
    "success": true,
    "data": {
        "response_to_player": "Стражник открывает ворота...",
        "player_memory": "Королевская печать открыла путь",
        "characters": [
            {
                "id": 1,
                "name": "Стражник",
                "attitude": "дружелюбное",
                "memory": "Запомнил игрока с печатью"
            }
        ],
        "chapter_break": false
    }
}
```
