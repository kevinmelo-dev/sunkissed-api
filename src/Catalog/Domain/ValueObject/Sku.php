<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\ValueObject;

use Src\Shared\Domain\Exception\DomainException;

final readonly class Sku
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if ($normalized === '') {
            throw new DomainException('O SKU não pode ser vazio.');
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
