<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ReactivateProduct;

use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class ReactivateProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(ReactivateProductCommand $command): Product
    {
        $existing = $this->products->find($command->id);

        if ($existing === null) {
            throw new ProductNotFoundException($command->id);
        }

        $reactivated = new Product(
            id: $existing->id(),
            type: $existing->type(),
            name: $existing->name(),
            slug: $existing->slug(),
            description: $existing->description(),
            active: true,
        );

        $saved = $this->products->save($reactivated);

        $this->audit->log(new AuditEvent(
            action: 'product.reactivated',
            actor: AuditActor::admin($command->actorId),
            subject: "product:{$saved->id()}",
            context: ['name' => $saved->name()],
        ));

        return $saved;
    }
}
