<?php

declare(strict_types=1);

namespace Src\Catalog\Application\DeactivateSize;

use Src\Catalog\Domain\Entity\Size;
use Src\Catalog\Domain\Exception\SizeNotFoundException;
use Src\Catalog\Domain\Repository\SizeRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class DeactivateSize
{
    public function __construct(
        private readonly SizeRepository $sizes,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(DeactivateSizeCommand $command): Size
    {
        $existing = $this->sizes->find($command->id);

        if ($existing === null) {
            throw new SizeNotFoundException($command->id);
        }

        $deactivated = new Size(
            id: $existing->id(),
            name: $existing->name(),
            sortOrder: $existing->sortOrder(),
            active: false,
        );

        $saved = $this->sizes->save($deactivated);

        $this->audit->log(new AuditEvent(
            action: 'size.deactivated',
            actor: AuditActor::admin($command->actorId),
            subject: "size:{$saved->id()}",
            context: ['name' => $saved->name()],
        ));

        return $saved;
    }
}
