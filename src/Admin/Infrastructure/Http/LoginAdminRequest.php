<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class LoginAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'O campo senha é obrigatório.',
            'password.string' => 'A senha deve ser um texto.',
        ];
    }
}
