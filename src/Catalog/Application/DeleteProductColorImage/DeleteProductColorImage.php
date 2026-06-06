<?php

declare(strict_types=1);

namespace Src\Catalog\Application\DeleteProductColorImage;

use Src\Catalog\Application\ImageStorage\ImageStorage;
use Src\Catalog\Domain\Exception\ProductColorImageNotFoundException;
use Src\Catalog\Domain\Repository\ProductColorImageRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class DeleteProductColorImage
{
    public function __construct(
        private readonly ProductColorImageRepository $images,
        private readonly ImageStorage $storage,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(DeleteProductColorImageCommand $command): void
    {
        $image = $this->images->find($command->imageId);

        if ($image === null) {
            throw new ProductColorImageNotFoundException($command->imageId);
        }

        $this->storage->delete($image->storageKey());
        $this->images->delete($command->imageId);

        $this->audit->log(new AuditEvent(
            action: 'product_color_image.deleted',
            actor: AuditActor::admin($command->actorId),
            subject: "product_color_image:{$command->imageId}",
            context: [
                'product_id' => $image->productId(),
                'color_id' => $image->colorId(),
                'storage_key' => $image->storageKey(),
            ],
        ));
    }
}
