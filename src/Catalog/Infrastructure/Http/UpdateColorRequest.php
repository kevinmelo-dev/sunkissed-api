<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'hex' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da cor é obrigatório.',
            'name.string' => 'O nome da cor deve ser um texto.',
            'name.max' => 'O nome da cor pode ter no máximo 100 caracteres.',
            'hex.string' => 'O código hexadecimal deve ser um texto.',
            'hex.regex' => 'O código hexadecimal deve estar no formato #RRGGBB (ex: #FF5733).',
        ];
    }
}
