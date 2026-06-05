<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UpdateSize;

use Src\Catalog\Domain\Entity\Size;
use Src\Catalog\Domain\Exception\DuplicateSizeNameException;
use Src\Catalog\Domain\Exception\SizeNotFoundException;
use Src\Catalog\Domain\Repository\SizeRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class UpdateSize
{
    public function __construct(
        private readonly SizeRepository $sizes,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(UpdateSizeCommand $command): Size
    {
        $existing = $this->sizes->find($command->id);

        if ($existing === null) {
            throw new SizeNotFoundException($command->id);
        }

        if ($this->sizes->existsByName($command->name, $command->id)) {
            throw new DuplicateSizeNameException($command->name);
        }

        $updated = new Size(
            id: $existing->id(),
            name: $command->name,
            sortOrder: $command->sortOrder,
            active: $existing->active(),
        );

        $saved = $this->sizes->save($updated);

        $this->audit->log(new AuditEvent(
            action: 'size.updated',
            actor: AuditActor::admin($command->actorId),
            subject: "size:{$saved->id()}",
            context: ['name' => $saved->name(), 'sort_order' => $saved->sortOrder()],
        ));

        return $saved;
    }
}
