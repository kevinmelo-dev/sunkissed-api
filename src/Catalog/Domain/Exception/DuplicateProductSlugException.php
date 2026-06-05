<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class DuplicateProductSlugException extends DomainException
{
    public function __construct(string $slug)
    {
        parent::__construct("Já existe um produto com o slug \"{$slug}\".");
    }

    public function errorCode(): string
    {
        return 'duplicate_product_slug';
    }
}
