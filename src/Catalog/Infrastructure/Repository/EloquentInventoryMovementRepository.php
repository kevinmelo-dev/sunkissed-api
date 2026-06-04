<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Repository;

use DateTimeImmutable;
use Src\Catalog\Domain\Entity\InventoryMovement;
use Src\Catalog\Domain\Enum\MovementType;
use Src\Catalog\Domain\Repository\InventoryMovementRepository;
use Src\Catalog\Infrastructure\Eloquent\InventoryMovementModel;

final class EloquentInventoryMovementRepository implements InventoryMovementRepository
{
    public function append(InventoryMovement $movement): InventoryMovement
    {
        $model = new InventoryMovementModel;
        $model->fill([
            'variant_id' => $movement->variantId(),
            'type' => $movement->type()->value,
            'quantity' => $movement->quantity(),
            'reason' => $movement->reason(),
            'reference' => $movement->reference(),
            'parent_movement_id' => $movement->parentMovementId(),
            'expires_at' => $movement->expiresAt()?->format('Y-m-d H:i:s'),
        ])->save();

        return $movement->withId($model->id);
    }

    public function find(int $id): ?InventoryMovement
    {
        $model = InventoryMovementModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function movementsForVariant(int $variantId): array
    {
        return InventoryMovementModel::where('variant_id', $variantId)
            ->orderBy('id')
            ->get()
            ->map(fn (InventoryMovementModel $m) => $this->toEntity($m))
            ->all();
    }

    public function activeReservation(int $reservationId): ?InventoryMovement
    {
        // A reservation is "active" if no child movement references it as parent.
        $model = InventoryMovementModel::where('id', $reservationId)
            ->where('type', MovementType::Reserva->value)
            ->whereNotExists(function ($query): void {
                $query->from('inventory_movements as child')
                    ->whereColumn('child.parent_movement_id', 'inventory_movements.id');
            })
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function expiredActiveReservations(DateTimeImmutable $now): array
    {
        return InventoryMovementModel::where('type', MovementType::Reserva->value)
            ->where('expires_at', '<=', $now->format('Y-m-d H:i:s'))
            ->whereNotExists(function ($query): void {
                $query->from('inventory_movements as child')
                    ->whereColumn('child.parent_movement_id', 'inventory_movements.id');
            })
            ->get()
            ->map(fn (InventoryMovementModel $m) => $this->toEntity($m))
            ->all();
    }

    private function toEntity(InventoryMovementModel $model): InventoryMovement
    {
        return InventoryMovement::reconstitute(
            id: $model->id,
            variantId: $model->variant_id,
            type: MovementType::from($model->type),
            quantity: $model->quantity,
            reason: $model->reason,
            reference: $model->reference,
            parentMovementId: $model->parent_movement_id,
            expiresAt: $model->expires_at ? new DateTimeImmutable($model->expires_at) : null,
            createdAt: $model->created_at ? new DateTimeImmutable($model->created_at) : null,
        );
    }
}
