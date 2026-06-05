<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class CategoryInactiveException extends DomainException
{
    public function __construct(int $categoryId)
    {
        parent::__construct("Categoria #{$categoryId} está inativa e não pode ser vinculada.");
    }

    public function errorCode(): string
    {
        return 'category_inactive';
    }
}
