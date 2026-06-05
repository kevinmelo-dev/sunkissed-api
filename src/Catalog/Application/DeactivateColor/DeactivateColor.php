<?php

declare(strict_types=1);

namespace Src\Catalog\Application\DeactivateColor;

use Src\Catalog\Domain\Entity\Color;
use Src\Catalog\Domain\Exception\ColorNotFoundException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class DeactivateColor
{
    public function __construct(
        private readonly ColorRepository $colors,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(DeactivateColorCommand $command): Color
    {
        $existing = $this->colors->find($command->id);

        if ($existing === null) {
            throw new ColorNotFoundException($command->id);
        }

        $deactivated = new Color(
            id: $existing->id(),
            name: $existing->name(),
            hex: $existing->hex(),
            active: false,
        );

        $saved = $this->colors->save($deactivated);

        $this->audit->log(new AuditEvent(
            action: 'color.deactivated',
            actor: AuditActor::admin($command->actorId),
            subject: "color:{$saved->id()}",
            context: ['name' => $saved->name()],
        ));

        return $saved;
    }
}
