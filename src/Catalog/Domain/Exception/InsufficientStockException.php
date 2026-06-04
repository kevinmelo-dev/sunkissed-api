<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class InsufficientStockException extends DomainException
{
    public function __construct(int $variantId, int $requested, int $available)
    {
        parent::__construct(
            "Estoque insuficiente para a variante #{$variantId}: solicitado {$requested}, disponível {$available}."
        );
    }

    public function errorCode(): string
    {
        return 'insufficient_stock';
    }
}
