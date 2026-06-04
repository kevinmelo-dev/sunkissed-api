<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\ValueObject;

use Src\Catalog\Domain\Enum\MovementType;
use Src\Shared\Domain\Exception\DomainException;

final readonly class MovementQuantity
{
    public function __construct(
        public int $value,
        public MovementType $type,
    ) {
        if ($value === 0) {
            throw new DomainException('A quantidade do movimento não pode ser zero.');
        }

        if ($value < 0 && ! $type->allowsNegativeQuantity()) {
            throw new DomainException('Quantidade negativa só é permitida em movimentos de ajuste.');
        }
    }

    public function absolute(): int
    {
        return abs($this->value);
    }
}
