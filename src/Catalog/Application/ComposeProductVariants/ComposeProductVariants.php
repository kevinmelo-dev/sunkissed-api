<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ComposeProductVariants;

use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Catalog\Domain\Exception\ColorNotFoundException;
use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Exception\SizeNotFoundException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Catalog\Domain\Repository\SizeRepository;
use Src\Catalog\Domain\Service\VariantCompositionService;
use Src\Catalog\Domain\ValueObject\Sku;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;
use Src\Shared\Domain\ValueObject\Money;

/**
 * Generates (or reconciles) the full variant matrix for a product.
 *
 * SKU convention: P{product_id}C{color_id}S{size_id} — e.g. P1C3S2.
 * Initial price for new variants: 0 cents (placeholder; edit via UpdateProductVariant).
 * Reactivated variants retain their existing price.
 */
final class ComposeProductVariants
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductVariantRepository $variants,
        private readonly ColorRepository $colors,
        private readonly SizeRepository $sizes,
        private readonly VariantCompositionService $compositionService,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(ComposeProductVariantsCommand $command): void
    {
        $product = $this->products->find($command->productId);

        if ($product === null) {
            throw new ProductNotFoundException($command->productId);
        }

        foreach ($command->colorIds as $colorId) {
            $color = $this->colors->find($colorId);

            if ($color === null) {
                throw new ColorNotFoundException($colorId);
            }

            if (! $color->active()) {
                throw new ColorNotFoundException($colorId);
            }
        }

        foreach ($command->sizeIds as $sizeId) {
            $size = $this->sizes->find($sizeId);

            if ($size === null) {
                throw new SizeNotFoundException($sizeId);
            }

            if (! $size->active()) {
                throw new SizeNotFoundException($sizeId);
            }
        }

        $currentVariants = $this->variants->findForProduct($command->productId);

        $result = $this->compositionService->compose(
            currentVariants: $currentVariants,
            desiredColorIds: $command->colorIds,
            desiredSizeIds: $command->sizeIds,
        );

        foreach ($result->toDeactivate as $variant) {
            $this->variants->save(new ProductVariant(
                id: $variant->id(),
                productId: $variant->productId(),
                colorId: $variant->colorId(),
                sizeId: $variant->sizeId(),
                sku: $variant->sku(),
                price: $variant->price(),
                active: false,
            ));
        }

        foreach ($result->toReactivate as $variant) {
            $this->variants->save(new ProductVariant(
                id: $variant->id(),
                productId: $variant->productId(),
                colorId: $variant->colorId(),
                sizeId: $variant->sizeId(),
                sku: $variant->sku(),
                price: $variant->price(),
                active: true,
            ));
        }

        foreach ($result->toCreate as $combo) {
            $sku = new Sku(sprintf('P%dC%dS%d', $command->productId, $combo['colorId'], $combo['sizeId']));

            $this->variants->save(new ProductVariant(
                id: null,
                productId: $command->productId,
                colorId: $combo['colorId'],
                sizeId: $combo['sizeId'],
                sku: $sku,
                price: Money::zero(),
                active: true,
            ));
        }

        $this->audit->log(new AuditEvent(
            action: 'product.variants_composed',
            actor: AuditActor::admin($command->actorId),
            subject: "product:{$command->productId}",
            context: [
                'color_ids' => $command->colorIds,
                'size_ids' => $command->sizeIds,
                'created' => count($result->toCreate),
                'reactivated' => count($result->toReactivate),
                'deactivated' => count($result->toDeactivate),
            ],
        ));
    }
}
