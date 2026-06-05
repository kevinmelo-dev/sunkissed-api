<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ReactivateColor;

use Src\Catalog\Domain\Entity\Color;
use Src\Catalog\Domain\Exception\ColorNotFoundException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class ReactivateColor
{
    public function __construct(
        private readonly ColorRepository $colors,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(ReactivateColorCommand $command): Color
    {
        $existing = $this->colors->find($command->id);

        if ($existing === null) {
            throw new ColorNotFoundException($command->id);
        }

        $reactivated = new Color(
            id: $existing->id(),
            name: $existing->name(),
            hex: $existing->hex(),
            active: true,
        );

        $saved = $this->colors->save($reactivated);

        $this->audit->log(new AuditEvent(
            action: 'color.reactivated',
            actor: AuditActor::admin($command->actorId),
            subject: "color:{$saved->id()}",
            context: ['name' => $saved->name()],
        ));

        return $saved;
    }
}
