<?php

declare(strict_types=1);

namespace Src\Catalog\Application\RegisterStockEntry;

use Src\Catalog\Domain\Enum\MovementType;
use Src\Catalog\Domain\Exception\VariantNotFoundException;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Catalog\Domain\Service\StockLedger;
use Src\Catalog\Domain\ValueObject\MovementQuantity;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class RegisterStockEntry
{
    public function __construct(
        private readonly ProductVariantRepository $variants,
        private readonly StockLedger $ledger,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(RegisterStockEntryCommand $command): StockEntryResult
    {
        $variant = $this->variants->find($command->variantId);

        if ($variant === null) {
            throw new VariantNotFoundException($command->variantId);
        }

        $qty = new MovementQuantity($command->quantity, MovementType::Entrada);

        $movement = $this->ledger->registerEntry($command->variantId, $qty, $command->reason);

        $balance = $this->ledger->availableFor($command->variantId);

        $this->audit->log(new AuditEvent(
            action: 'stock.entry_registered',
            actor: AuditActor::admin($command->actorId),
            subject: "variant:{$command->variantId}",
            context: [
                'movement_id' => $movement->id(),
                'quantity' => $command->quantity,
                'reason' => $command->reason,
            ],
        ));

        return new StockEntryResult(
            movementId: $movement->id(),
            availableAfter: $balance->available,
        );
    }
}
