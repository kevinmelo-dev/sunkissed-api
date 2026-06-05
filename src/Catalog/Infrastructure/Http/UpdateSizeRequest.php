<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tamanho é obrigatório.',
            'name.string' => 'O nome do tamanho deve ser um texto.',
            'name.max' => 'O nome do tamanho pode ter no máximo 50 caracteres.',
            'sort_order.required' => 'A ordem de exibição é obrigatória.',
            'sort_order.integer' => 'A ordem de exibição deve ser um número inteiro.',
            'sort_order.min' => 'A ordem de exibição deve ser maior ou igual a zero.',
        ];
    }
}
