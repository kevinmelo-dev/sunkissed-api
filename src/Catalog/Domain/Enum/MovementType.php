<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Enum;

enum MovementType: string
{
    case Entrada = 'entrada';
    case Saida = 'saida';
    case Reserva = 'reserva';
    case Liberacao = 'liberacao';
    case Ajuste = 'ajuste';

    /**
     * Sign applied to this movement type when calculating available stock.
     * Ajuste can be positive or negative — the actual sign comes from quantity.
     * For all other types the sign is fixed.
     */
    public function availabilitySign(): int
    {
        return match ($this) {
            self::Entrada => +1,
            self::Saida => -1,
            self::Reserva => -1,
            self::Liberacao => 0,
            self::Ajuste => +1,
        };
    }

    public function allowsNegativeQuantity(): bool
    {
        return $this === self::Ajuste;
    }
}
