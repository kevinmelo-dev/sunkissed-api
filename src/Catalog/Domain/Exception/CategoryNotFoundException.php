<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class CategoryNotFoundException extends DomainException
{
    public function __construct(int $categoryId)
    {
        parent::__construct("Categoria #{$categoryId} não encontrada.");
    }

    public function httpStatus(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'category_not_found';
    }
}
