<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UploadProductColorImage;

use Src\Catalog\Application\ImageStorage\ImageStorage;
use Src\Catalog\Domain\Entity\ProductColorImage;
use Src\Catalog\Domain\Exception\ColorInactiveException;
use Src\Catalog\Domain\Exception\ColorNotAssociatedWithProductException;
use Src\Catalog\Domain\Exception\ColorNotFoundException;
use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Catalog\Domain\Repository\ProductColorImageRepository;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class UploadProductColorImage
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ColorRepository $colors,
        private readonly ProductVariantRepository $variants,
        private readonly ProductColorImageRepository $images,
        private readonly ImageStorage $storage,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(UploadProductColorImageCommand $command): ProductColorImage
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

        $prefix = "products/{$command->productId}/colors/{$command->colorId}";
        $storageKey = $this->storage->store($command->fileTempPath, $command->mimeType, $prefix);

        $position = $this->images->nextPosition($command->productId, $command->colorId);

        $image = $this->images->save(new ProductColorImage(
            id: null,
            productId: $command->productId,
            colorId: $command->colorId,
            storageKey: $storageKey,
            position: $position,
        ));

        $this->audit->log(new AuditEvent(
            action: 'product_color_image.added',
            actor: AuditActor::admin($command->actorId),
            subject: "product_color_image:{$image->id()}",
            context: [
                'product_id' => $command->productId,
                'color_id' => $command->colorId,
                'storage_key' => $storageKey,
                'position' => $position,
            ],
        ));

        return $image;
    }
}
