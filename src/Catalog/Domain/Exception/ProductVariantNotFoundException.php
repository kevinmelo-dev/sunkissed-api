<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class ProductVariantNotFoundException extends DomainException
{
    public function __construct(int $variantId)
    {
        parent::__construct("Variante #{$variantId} não encontrada.");
    }

    public function httpStatus(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'product_variant_not_found';
    }
}
