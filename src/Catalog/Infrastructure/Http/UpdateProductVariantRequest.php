<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'image' => ['sometimes', 'nullable', 'string', 'max:500'],
            'sku' => ['sometimes', 'string', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_cents.integer' => 'O preço deve ser um número inteiro em centavos.',
            'price_cents.min' => 'O preço não pode ser negativo.',
            'image.max' => 'O caminho da imagem pode ter no máximo 500 caracteres.',
            'sku.min' => 'O SKU não pode ser vazio.',
            'sku.max' => 'O SKU pode ter no máximo 100 caracteres.',
        ];
    }
}
