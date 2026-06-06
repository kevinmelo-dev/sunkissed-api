<?php

declare(strict_types=1);

namespace Src\Catalog\Application\SetProductCoverColor;

use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Exception\ColorInactiveException;
use Src\Catalog\Domain\Exception\ColorNotAssociatedWithProductException;
use Src\Catalog\Domain\Exception\ColorNotFoundException;
use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class SetProductCoverColor
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ColorRepository $colors,
        private readonly ProductVariantRepository $variants,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(SetProductCoverColorCommand $command): Product
    {
        $product = $this->products->find($command->productId);

        if ($product === null) {
            throw new ProductNotFoundException($command->productId);
        }

        $color = $this->colors->find($command->colorId);

        if ($color === null) {
            throw new ColorNotFoundException($command->colorId);
        }

        if (! $color->active()) {
            throw new ColorInactiveException($command->colorId);
        }

        if (! $this->variants->existsColorForProduct($command->productId, $command->colorId)) {
            throw new ColorNotAssociatedWithProductException($command->colorId, $command->productId);
        }

        $updated = new Product(
            id: $product->id(),
            type: $product->type(),
            name: $product->name(),
            slug: $product->slug(),
            description: $product->description(),
            active: $product->active(),
            coverColorId: $command->colorId,
        );

        $saved = $this->products->save($updated);

        $this->audit->log(new AuditEvent(
            action: 'product.cover_color_set',
            actor: AuditActor::admin($command->actorId),
            subject: "product:{$saved->id()}",
            context: ['cover_color_id' => $command->colorId],
        ));

        return $saved;
    }
}
