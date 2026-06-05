<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class SyncProductCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_ids' => ['required', 'array'],
            'category_ids.*' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_ids.required' => 'A lista de categorias é obrigatória.',
            'category_ids.array' => 'As categorias devem ser enviadas como uma lista.',
            'category_ids.*.integer' => 'Cada ID de categoria deve ser um número inteiro.',
            'category_ids.*.min' => 'Cada ID de categoria deve ser maior que zero.',
        ];
    }
}
