<?php

declare(strict_types=1);

namespace Src\Catalog\Application\CreateSize;

use Src\Catalog\Domain\Entity\Size;
use Src\Catalog\Domain\Exception\DuplicateSizeNameException;
use Src\Catalog\Domain\Repository\SizeRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class CreateSize
{
    public function __construct(
        private readonly SizeRepository $sizes,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(CreateSizeCommand $command): Size
    {
        if ($this->sizes->existsByName($command->name)) {
            throw new DuplicateSizeNameException($command->name);
        }

        $size = new Size(
            id: null,
            name: $command->name,
            sortOrder: $command->sortOrder,
            active: true,
        );

        $saved = $this->sizes->save($size);

        $this->audit->log(new AuditEvent(
            action: 'size.created',
            actor: AuditActor::admin($command->actorId),
            subject: "size:{$saved->id()}",
            context: ['name' => $saved->name(), 'sort_order' => $saved->sortOrder()],
        ));

        return $saved;
    }
}
