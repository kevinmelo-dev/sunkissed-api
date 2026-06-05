<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class DuplicateSizeNameException extends DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Já existe um tamanho com o nome \"{$name}\".");
    }

    public function errorCode(): string
    {
        return 'duplicate_size_name';
    }
}
