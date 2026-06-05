<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:kit,single'],
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'O tipo do produto é obrigatório.',
            'type.in' => 'O tipo do produto deve ser "kit" ou "single".',
            'name.required' => 'O nome do produto é obrigatório.',
            'name.max' => 'O nome do produto pode ter no máximo 200 caracteres.',
            'slug.required' => 'O slug do produto é obrigatório.',
            'slug.max' => 'O slug pode ter no máximo 200 caracteres.',
            'slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hifens (ex: biquini-floral).',
        ];
    }
}
