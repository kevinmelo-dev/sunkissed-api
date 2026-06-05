<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UpdateColor;

use Src\Catalog\Domain\Entity\Color;
use Src\Catalog\Domain\Exception\ColorNotFoundException;
use Src\Catalog\Domain\Exception\DuplicateColorNameException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class UpdateColor
{
    public function __construct(
        private readonly ColorRepository $colors,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(UpdateColorCommand $command): Color
    {
        $existing = $this->colors->find($command->id);

        if ($existing === null) {
            throw new ColorNotFoundException($command->id);
        }

        if ($this->colors->existsByName($command->name, $command->id)) {
            throw new DuplicateColorNameException($command->name);
        }

        $updated = new Color(
            id: $existing->id(),
            name: $command->name,
            hex: $command->hex,
            active: $existing->active(),
        );

        $saved = $this->colors->save($updated);

        $this->audit->log(new AuditEvent(
            action: 'color.updated',
            actor: AuditActor::admin($command->actorId),
            subject: "color:{$saved->id()}",
            context: ['name' => $saved->name(), 'hex' => $saved->hex()],
        ));

        return $saved;
    }
}
