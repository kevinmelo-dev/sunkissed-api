<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class ProductColorImageNotFoundException extends DomainException
{
    public function __construct(int $imageId)
    {
        parent::__construct("Imagem #{$imageId} não encontrada.");
    }

    public function httpStatus(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'product_color_image_not_found';
    }
}
