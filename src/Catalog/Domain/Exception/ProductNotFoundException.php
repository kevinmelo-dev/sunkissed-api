<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class ProductNotFoundException extends DomainException
{
    public function __construct(int $productId)
    {
        parent::__construct("Produto #{$productId} não encontrado.");
    }

    public function httpStatus(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'product_not_found';
    }
}
