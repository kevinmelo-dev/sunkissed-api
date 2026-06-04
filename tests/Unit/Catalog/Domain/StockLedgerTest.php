<?php

declare(strict_types=1);

use Src\Catalog\Domain\Entity\InventoryMovement;
use Src\Catalog\Domain\Enum\MovementType;
use Src\Catalog\Domain\Exception\InsufficientStockException;
use Src\Catalog\Domain\Exception\InvalidReservationException;
use Src\Catalog\Domain\Repository\InventoryMovementRepository;
use Src\Catalog\Domain\Service\StockLedger;
use Src\Catalog\Domain\ValueObject\MovementQuantity;

function fakeMovementRepo(): InventoryMovementRepository
{
    return new class implements InventoryMovementRepository
    {
        /** @var InventoryMovement[] */
        public array $movements = [];

        private int $nextId = 1;

        public function append(InventoryMovement $movement): InventoryMovement
        {
            $persisted = $movement->withId($this->nextId++);
            $this->movements[] = $persisted;

            return $persisted;
        }

        public function find(int $id): ?InventoryMovement
        {
            foreach ($this->movements as $m) {
                if ($m->id() === $id) {
                    return $m;
                }
            }

            return null;
        }

        public function movementsForVariant(int $variantId): array
        {
            return array_values(array_filter(
                $this->movements,
                fn (InventoryMovement $m) => $m->variantId() === $variantId,
            ));
        }

        public function activeReservation(int $reservationId): ?InventoryMovement
        {
            $reservation = $this->find($reservationId);

            if ($reservation === null || $reservation->type() !== MovementType::Reserva) {
                return null;
            }

            foreach ($this->movements as $m) {
                if ($m->parentMovementId() === $reservationId) {
                    return null;
                }
            }

            return $reservation;
        }

        public function expiredActiveReservations(DateTimeImmutable $now): array
        {
            return array_values(array_filter(
                $this->movements,
                function (InventoryMovement $m) use ($now): bool {
                    if ($m->type() !== MovementType::Reserva) {
                        return false;
                    }
                    if ($m->expiresAt() === null || $m->expiresAt() > $now) {
                        return false;
                    }
                    foreach ($this->movements as $child) {
                        if ($child->parentMovementId() === $m->id()) {
                            return false;
                        }
                    }

                    return true;
                }
            ));
        }
    };
}

it('available stock is zero when ledger is empty', function (): void {
    $repo = fakeMovementRepo();
    $ledger = new StockLedger($repo);

    expect($ledger->availableFor(1)->available)->toBe(0);
});

it('entrada increases available stock', function (): void {
    $repo = fakeMovementRepo();
    $ledger = new StockLedger($repo);

    $ledger->registerEntry(1, new MovementQuantity(10, MovementType::Entrada), null);

    expect($ledger->availableFor(1)->available)->toBe(10);
});

it('reserva reduces available stock', function (): void {
    $repo = fakeMovementRepo();
    $ledger = new StockLedger($repo);

    $ledger->registerEntry(1, new MovementQuantity(10, MovementType::Entrada), null);
    $ledger->reserve(1, new MovementQuantity(3, MovementType::Reserva), 'order:1', new DateTimeImmutable('+1 hour'));

    expect($ledger->availableFor(1)->available)->toBe(7);
});

it('commitReservation creates a saida and neutralises the reserva', function (): void {
    $repo = fakeMovementRepo();
    $ledger = new StockLedger($repo);

    $ledger->registerEntry(1, new MovementQuantity(10, MovementType::Entrada), null);
    $reservation = $ledger->reserve(1, new MovementQuantity(3, MovementType::Reserva), 'order:1', new DateTimeImmutable('+1 hour'));

    $saida = $ledger->commitReservation($reservation->id());

    expect($saida->type())->toBe(MovementType::Saida)
        ->and($saida->parentMovementId())->toBe($reservation->id())
        ->and($ledger->availableFor(1)->available)->toBe(7);
});

it('releaseReservation creates liberacao and restores available stock', function (): void {
    $repo = fakeMovementRepo();
    $ledger = new StockLedger($repo);

    $ledger->registerEntry(1, new MovementQuantity(10, MovementType::Entrada), null);
    $reservation = $ledger->reserve(1, new MovementQuantity(3, MovementType::Reserva), 'order:1', new DateTimeImmutable('+1 hour'));

    $ledger->releaseReservation($reservation->id(), 'expirado');

    expect($ledger->availableFor(1)->available)->toBe(10);
});

it('reserve throws InsufficientStockException when stock is too low', function (): void {
    $repo = fakeMovementRepo();
    $ledger = new StockLedger($repo);

    $ledger->registerEntry(1, new MovementQuantity(2, MovementType::Entrada), null);

    $ledger->reserve(1, new MovementQuantity(5, MovementType::Reserva), 'order:1', new DateTimeImmutable('+1 hour'));
})->throws(InsufficientStockException::class);

it('commitReservation throws if reservation already consumed', function (): void {
    $repo = fakeMovementRepo();
    $ledger = new StockLedger($repo);

    $ledger->registerEntry(1, new MovementQuantity(10, MovementType::Entrada), null);
    $reservation = $ledger->reserve(1, new MovementQuantity(3, MovementType::Reserva), 'order:1', new DateTimeImmutable('+1 hour'));
    $ledger->commitReservation($reservation->id());

    $ledger->commitReservation($reservation->id());
})->throws(InvalidReservationException::class);

it('positive ajuste increases available stock', function (): void {
    $repo = fakeMovementRepo();
    $ledger = new StockLedger($repo);

    $ledger->adjust(1, new MovementQuantity(5, MovementType::Ajuste), 'correcting count');

    expect($ledger->availableFor(1)->available)->toBe(5);
});

it('negative ajuste decreases available stock', function (): void {
    $repo = fakeMovementRepo();
    $ledger = new StockLedger($repo);

    $ledger->registerEntry(1, new MovementQuantity(10, MovementType::Entrada), null);
    $ledger->adjust(1, new MovementQuantity(-3, MovementType::Ajuste), 'damage');

    expect($ledger->availableFor(1)->available)->toBe(7);
});
