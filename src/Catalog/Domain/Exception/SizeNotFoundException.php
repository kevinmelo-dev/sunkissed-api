<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class SizeNotFoundException extends DomainException
{
    public function __construct(int $sizeId)
    {
        parent::__construct("Tamanho #{$sizeId} não encontrado.");
    }

    public function httpStatus(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'size_not_found';
    }
}
