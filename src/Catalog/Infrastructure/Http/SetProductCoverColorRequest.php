<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class SetProductCoverColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'color_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'color_id.required' => 'O campo color_id é obrigatório.',
            'color_id.integer' => 'O color_id deve ser um número inteiro.',
            'color_id.min' => 'O color_id deve ser válido.',
        ];
    }
}
