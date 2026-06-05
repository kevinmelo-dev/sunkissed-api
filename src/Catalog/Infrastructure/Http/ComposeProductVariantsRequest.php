<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class ComposeProductVariantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'color_ids' => ['required', 'array', 'min:1'],
            'color_ids.*' => ['required', 'integer', 'min:1'],
            'size_ids' => ['required', 'array', 'min:1'],
            'size_ids.*' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'color_ids.required' => 'A lista de cores é obrigatória.',
            'color_ids.array' => 'As cores devem ser enviadas como uma lista.',
            'color_ids.min' => 'Selecione ao menos uma cor.',
            'color_ids.*.integer' => 'Cada ID de cor deve ser um número inteiro.',
            'size_ids.required' => 'A lista de tamanhos é obrigatória.',
            'size_ids.array' => 'Os tamanhos devem ser enviados como uma lista.',
            'size_ids.min' => 'Selecione ao menos um tamanho.',
            'size_ids.*.integer' => 'Cada ID de tamanho deve ser um número inteiro.',
        ];
    }
}
