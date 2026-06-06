<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class UploadProductColorImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'O arquivo de imagem é obrigatório.',
            'image.file' => 'O campo enviado deve ser um arquivo.',
            'image.mimetypes' => 'A imagem deve estar nos formatos JPEG, PNG ou WebP.',
            'image.max' => 'A imagem não pode ultrapassar 5 MB.',
        ];
    }
}
