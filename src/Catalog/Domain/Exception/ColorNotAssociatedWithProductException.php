<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class ColorNotAssociatedWithProductException extends DomainException
{
    public function __construct(int $colorId, int $productId)
    {
        parent::__construct("A cor #{$colorId} não está associada ao produto #{$productId}.");
    }

    public function httpStatus(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'color_not_associated_with_product';
    }
}
