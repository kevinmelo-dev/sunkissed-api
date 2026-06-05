<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class InvalidCategoryHierarchyException extends DomainException
{
    public function __construct()
    {
        parent::__construct('A categoria pai deve existir, estar ativa e ser uma categoria raiz. A hierarquia suporta no máximo dois níveis.');
    }

    public function errorCode(): string
    {
        return 'invalid_category_hierarchy';
    }
}
