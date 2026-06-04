<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Entity;

use DateTimeImmutable;
use Src\Catalog\Domain\Enum\MovementType;
use Src\Catalog\Domain\Exception\InvalidReservationException;

final class InventoryMovement
{
    private function __construct(
        private readonly ?int $id,
        private readonly int $variantId,
        private readonly MovementType $type,
        private readonly int $quantity,
        private readonly ?string $reason,
        private readonly ?string $reference,
        private readonly ?int $parentMovementId,
        private readonly ?DateTimeImmutable $expiresAt,
        private readonly ?DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        int $variantId,
        MovementType $type,
        int $quantity,
        ?string $reason = null,
        ?string $reference = null,
        ?int $parentMovementId = null,
        ?DateTimeImmutable $expiresAt = null,
    ): self {
        if ($expiresAt !== null && $type !== MovementType::Reserva) {
            throw new InvalidReservationException('expires_at só é permitido em reservas.');
        }

        if ($parentMovementId !== null && ! in_array($type, [MovementType::Saida, MovementType::Liberacao], true)) {
            throw new InvalidReservationException('parent_movement_id só é permitido em saída e liberação.');
        }

        return new self(
            id: null,
            variantId: $variantId,
            type: $type,
            quantity: $quantity,
            reason: $reason,
            reference: $reference,
            parentMovementId: $parentMovementId,
            expiresAt: $expiresAt,
            createdAt: null,
        );
    }

    public static function reconstitute(
        int $id,
        int $variantId,
        MovementType $type,
        int $quantity,
        ?string $reason,
        ?string $reference,
        ?int $parentMovementId,
        ?DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $variantId, $type, $quantity, $reason, $reference, $parentMovementId, $expiresAt, $createdAt);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function variantId(): int
    {
        return $this->variantId;
    }

    public function type(): MovementType
    {
        return $this->type;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }

    public function parentMovementId(): ?int
    {
        return $this->parentMovementId;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->variantId,
            $this->type,
            $this->quantity,
            $this->reason,
            $this->reference,
            $this->parentMovementId,
            $this->expiresAt,
            $this->createdAt,
        );
    }
}
