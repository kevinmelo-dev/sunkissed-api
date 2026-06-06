<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderProductColorImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image_ids' => ['required', 'array', 'min:1'],
            'image_ids.*' => ['integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'image_ids.required' => 'A lista de imagens é obrigatória.',
            'image_ids.array' => 'O campo image_ids deve ser uma lista.',
            'image_ids.min' => 'A lista de imagens não pode ser vazia.',
            'image_ids.*.integer' => 'Cada item da lista deve ser um número inteiro.',
        ];
    }
}
