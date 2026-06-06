<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class ColorInactiveException extends DomainException
{
    public function __construct(int $colorId)
    {
        parent::__construct("A cor #{$colorId} está inativa.");
    }

    public function httpStatus(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'color_inactive';
    }
}
