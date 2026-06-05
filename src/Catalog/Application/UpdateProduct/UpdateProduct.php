<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UpdateProduct;

use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Enum\ProductType;
use Src\Catalog\Domain\Exception\DuplicateProductSlugException;
use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class UpdateProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(UpdateProductCommand $command): Product
    {
        $existing = $this->products->find($command->id);

        if ($existing === null) {
            throw new ProductNotFoundException($command->id);
        }

        if ($this->products->existsBySlug($command->slug, $command->id)) {
            throw new DuplicateProductSlugException($command->slug);
        }

        $updated = new Product(
            id: $existing->id(),
            type: ProductType::from($command->type),
            name: $command->name,
            slug: $command->slug,
            description: $command->description,
            active: $command->active,
        );

        $saved = $this->products->save($updated);

        $this->audit->log(new AuditEvent(
            action: 'product.updated',
            actor: AuditActor::admin($command->actorId),
            subject: "product:{$saved->id()}",
            context: ['name' => $saved->name(), 'slug' => $saved->slug()],
        ));

        return $saved;
    }
}
