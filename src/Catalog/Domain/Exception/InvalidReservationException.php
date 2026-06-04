<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class InvalidReservationException extends DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct("Reserva inválida: {$reason}");
    }

    public function errorCode(): string
    {
        return 'invalid_reservation';
    }
}
