<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class DuplicateSkuException extends DomainException
{
    public function __construct(string $sku)
    {
        parent::__construct("Já existe uma variante com o SKU \"{$sku}\".");
    }

    public function errorCode(): string
    {
        return 'duplicate_sku';
    }
}
