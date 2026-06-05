<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UpdateProductVariant;

use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Catalog\Domain\Exception\DuplicateSkuException;
use Src\Catalog\Domain\Exception\ProductVariantNotFoundException;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Catalog\Domain\ValueObject\Sku;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;
use Src\Shared\Domain\ValueObject\Money;

final class UpdateProductVariant
{
    public function __construct(
        private readonly ProductVariantRepository $variants,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(UpdateProductVariantCommand $command): ProductVariant
    {
        $existing = $this->variants->find($command->id);

        if ($existing === null) {
            throw new ProductVariantNotFoundException($command->id);
        }

        $newSku = $existing->sku();

        if ($command->sku !== null) {
            $candidateSku = new Sku($command->sku);

            if (! $candidateSku->equals($existing->sku())) {
                $conflict = $this->variants->findBySku($candidateSku);

                if ($conflict !== null) {
                    throw new DuplicateSkuException($candidateSku->value);
                }
            }

            $newSku = $candidateSku;
        }

        $newPrice = $command->priceCents !== null
            ? Money::fromCents($command->priceCents)
            : $existing->price();

        $newImage = $command->image !== null ? $command->image : $existing->image();

        $updated = new ProductVariant(
            id: $existing->id(),
            productId: $existing->productId(),
            colorId: $existing->colorId(),
            sizeId: $existing->sizeId(),
            sku: $newSku,
            price: $newPrice,
            active: $existing->active(),
            image: $newImage,
        );

        $saved = $this->variants->save($updated);

        $this->audit->log(new AuditEvent(
            action: 'product_variant.updated',
            actor: AuditActor::admin($command->actorId),
            subject: "variant:{$saved->id()}",
            context: [
                'sku' => $saved->sku()->value,
                'price_cents' => $saved->price()->cents,
                'image' => $saved->image(),
            ],
        ));

        return $saved;
    }
}
