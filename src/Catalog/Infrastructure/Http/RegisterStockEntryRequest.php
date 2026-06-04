<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterStockEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'variant_id.required' => 'O campo variante é obrigatório.',
            'variant_id.integer' => 'O identificador da variante deve ser um número inteiro.',
            'quantity.required' => 'O campo quantidade é obrigatório.',
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity.min' => 'A quantidade mínima é 1.',
            'reason.string' => 'O motivo deve ser um texto.',
            'reason.max' => 'O motivo pode ter no máximo 255 caracteres.',
        ];
    }
}
