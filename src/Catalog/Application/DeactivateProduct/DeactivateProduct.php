<?php

declare(strict_types=1);

namespace Src\Catalog\Application\DeactivateProduct;

use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class DeactivateProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(DeactivateProductCommand $command): Product
    {
        $existing = $this->products->find($command->id);

        if ($existing === null) {
            throw new ProductNotFoundException($command->id);
        }

        $deactivated = new Product(
            id: $existing->id(),
            type: $existing->type(),
            name: $existing->name(),
            slug: $existing->slug(),
            description: $existing->description(),
            active: false,
        );

        $saved = $this->products->save($deactivated);

        $this->audit->log(new AuditEvent(
            action: 'product.deactivated',
            actor: AuditActor::admin($command->actorId),
            subject: "product:{$saved->id()}",
            context: ['name' => $saved->name()],
        ));

        return $saved;
    }
}
