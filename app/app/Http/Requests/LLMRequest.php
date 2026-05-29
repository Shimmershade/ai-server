<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\RequestType;

class LLMRequest extends FormRequest
{
    
    public function rules(): array
    {
        return [
            'request_type' => ['required', 'string', 'in:game_post,chapter_end,generate_plotpoints'],
            
            // Общие поля для всех типов
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['nullable', 'integer', 'min:100', 'max:4000'],
            
            // Для game_post
            'player_memory' => ['required_if:request_type,game_post', 'nullable', 'string'],
            'characters' => ['required_if:request_type,game_post', 'nullable', 'array'],
            'characters.*.id' => ['required_with:characters', 'integer'],
            'characters.*.name' => ['required_with:characters', 'string'],
            'characters.*.attitude' => ['required_with:characters', 'string'],
            'characters.*.memory' => ['required_with:characters', 'string'],
            'last_ai_post' => ['required_if:request_type,game_post', 'nullable', 'string'],
            'last_player_post' => ['required_if:request_type,game_post', 'nullable', 'string'],
            'previous_result' => ['nullable', 'string'],
            
            // Для chapter_end
            'chapter_title' => ['required_if:request_type,chapter_end', 'nullable', 'string'],
            'chapter_summary' => ['required_if:request_type,chapter_end', 'nullable', 'string'],
            'plotpoints' => ['required_if:request_type,chapter_end', 'nullable', 'array'],
            'previous_chapters_results' => ['nullable', 'array'],
            
            // Для generate_plotpoints
            'chapter_text' => ['required_if:request_type,generate_plotpoints', 'nullable', 'string'],
            'global_plot' => ['required_if:request_type,generate_plotpoints', 'nullable', 'string'],
            'previous_chapter_result' => ['required_if:request_type,generate_plotpoints', 'nullable', 'string'],
            'count' => ['required_if:request_type,generate_plotpoints', 'nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'request_type.required' => 'Поле request_type обязательно',
            'request_type.in' => 'request_type должен быть: game_post, chapter_end, generate_plotpoints',
        ];
    }
}