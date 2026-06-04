<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Repository;

use DateTimeImmutable;
use Src\Catalog\Domain\Entity\InventoryMovement;

interface InventoryMovementRepository
{
    public function append(InventoryMovement $movement): InventoryMovement;

    public function find(int $id): ?InventoryMovement;

    /** @return InventoryMovement[] */
    public function movementsForVariant(int $variantId): array;

    public function activeReservation(int $reservationId): ?InventoryMovement;

    /** @return InventoryMovement[] */
    public function expiredActiveReservations(DateTimeImmutable $now): array;
}
