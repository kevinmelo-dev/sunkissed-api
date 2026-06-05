<?php

declare(strict_types=1);

namespace Src\Catalog\Application\CreateProduct;

use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Enum\ProductType;
use Src\Catalog\Domain\Exception\DuplicateProductSlugException;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class CreateProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(CreateProductCommand $command): Product
    {
        if ($this->products->existsBySlug($command->slug)) {
            throw new DuplicateProductSlugException($command->slug);
        }

        $product = new Product(
            id: null,
            type: ProductType::from($command->type),
            name: $command->name,
            slug: $command->slug,
            description: $command->description,
            active: true,
        );

        $saved = $this->products->save($product);

        $this->audit->log(new AuditEvent(
            action: 'product.created',
            actor: AuditActor::admin($command->actorId),
            subject: "product:{$saved->id()}",
            context: ['name' => $saved->name(), 'slug' => $saved->slug(), 'type' => $saved->type()->value],
        ));

        return $saved;
    }
}
