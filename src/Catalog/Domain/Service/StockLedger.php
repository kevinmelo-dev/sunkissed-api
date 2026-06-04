<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Service;

use DateTimeImmutable;
use Src\Catalog\Domain\Entity\InventoryMovement;
use Src\Catalog\Domain\Enum\MovementType;
use Src\Catalog\Domain\Exception\InsufficientStockException;
use Src\Catalog\Domain\Exception\InvalidReservationException;
use Src\Catalog\Domain\Repository\InventoryMovementRepository;
use Src\Catalog\Domain\ValueObject\MovementQuantity;
use Src\Catalog\Domain\ValueObject\StockBalance;

final class StockLedger
{
    public function __construct(
        private readonly InventoryMovementRepository $movements,
    ) {}

    public function availableFor(int $variantId): StockBalance
    {
        $movements = $this->movements->movementsForVariant($variantId);

        $reservationIds = [];
        foreach ($movements as $m) {
            if ($m->type() === MovementType::Reserva) {
                $reservationIds[$m->id()] = true;
            }
        }

        // Mark reservations that have been consumed (have a child saida/liberacao)
        $consumedReservationIds = [];
        foreach ($movements as $m) {
            if ($m->parentMovementId() !== null) {
                $consumedReservationIds[$m->parentMovementId()] = true;
            }
        }

        $available = 0;

        foreach ($movements as $m) {
            $sign = $m->type()->availabilitySign();

            if ($sign === 0) {
                continue;
            }

            if ($m->type() === MovementType::Reserva) {
                // Only count active (non-consumed) reservations
                if (isset($consumedReservationIds[$m->id()])) {
                    continue;
                }
                $available -= $m->quantity();

                continue;
            }

            $available += $sign * $m->quantity();
        }

        return new StockBalance($variantId, $available);
    }

    public function registerEntry(int $variantId, MovementQuantity $qty, ?string $reason): InventoryMovement
    {
        $movement = InventoryMovement::create(
            variantId: $variantId,
            type: MovementType::Entrada,
            quantity: $qty->value,
            reason: $reason,
        );

        return $this->movements->append($movement);
    }

    public function adjust(int $variantId, MovementQuantity $signedQty, string $reason): InventoryMovement
    {
        $movement = InventoryMovement::create(
            variantId: $variantId,
            type: MovementType::Ajuste,
            quantity: $signedQty->value,
            reason: $reason,
        );

        return $this->movements->append($movement);
    }

    public function reserve(
        int $variantId,
        MovementQuantity $qty,
        string $reference,
        DateTimeImmutable $expiresAt,
    ): InventoryMovement {
        $balance = $this->availableFor($variantId);

        if (! $balance->isAvailable($qty->value)) {
            throw new InsufficientStockException($variantId, $qty->value, $balance->available);
        }

        $movement = InventoryMovement::create(
            variantId: $variantId,
            type: MovementType::Reserva,
            quantity: $qty->value,
            reference: $reference,
            expiresAt: $expiresAt,
        );

        return $this->movements->append($movement);
    }

    public function commitReservation(int $reservationId): InventoryMovement
    {
        $reservation = $this->movements->activeReservation($reservationId);

        if ($reservation === null) {
            throw new InvalidReservationException("reserva #{$reservationId} não encontrada ou já consumida.");
        }

        $saida = InventoryMovement::create(
            variantId: $reservation->variantId(),
            type: MovementType::Saida,
            quantity: $reservation->quantity(),
            parentMovementId: $reservation->id(),
        );

        return $this->movements->append($saida);
    }

    public function releaseReservation(int $reservationId, string $reason): InventoryMovement
    {
        $reservation = $this->movements->activeReservation($reservationId);

        if ($reservation === null) {
            throw new InvalidReservationException("reserva #{$reservationId} não encontrada ou já consumida.");
        }

        $liberacao = InventoryMovement::create(
            variantId: $reservation->variantId(),
            type: MovementType::Liberacao,
            quantity: $reservation->quantity(),
            reason: $reason,
            parentMovementId: $reservation->id(),
        );

        return $this->movements->append($liberacao);
    }
}
