<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ReactivateSize;

use Src\Catalog\Domain\Entity\Size;
use Src\Catalog\Domain\Exception\SizeNotFoundException;
use Src\Catalog\Domain\Repository\SizeRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class ReactivateSize
{
    public function __construct(
        private readonly SizeRepository $sizes,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(ReactivateSizeCommand $command): Size
    {
        $existing = $this->sizes->find($command->id);

        if ($existing === null) {
            throw new SizeNotFoundException($command->id);
        }

        $reactivated = new Size(
            id: $existing->id(),
            name: $existing->name(),
            sortOrder: $existing->sortOrder(),
            active: true,
        );

        $saved = $this->sizes->save($reactivated);

        $this->audit->log(new AuditEvent(
            action: 'size.reactivated',
            actor: AuditActor::admin($command->actorId),
            subject: "size:{$saved->id()}",
            context: ['name' => $saved->name()],
        ));

        return $saved;
    }
}
