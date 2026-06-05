<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class ColorNotFoundException extends DomainException
{
    public function __construct(int $colorId)
    {
        parent::__construct("Cor #{$colorId} não encontrada.");
    }

    public function httpStatus(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'color_not_found';
    }
}
