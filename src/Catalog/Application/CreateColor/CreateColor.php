<?php

declare(strict_types=1);

namespace Src\Catalog\Application\CreateColor;

use Src\Catalog\Domain\Entity\Color;
use Src\Catalog\Domain\Exception\DuplicateColorNameException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class CreateColor
{
    public function __construct(
        private readonly ColorRepository $colors,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(CreateColorCommand $command): Color
    {
        if ($this->colors->existsByName($command->name)) {
            throw new DuplicateColorNameException($command->name);
        }

        $color = new Color(
            id: null,
            name: $command->name,
            hex: $command->hex,
            active: true,
        );

        $saved = $this->colors->save($color);

        $this->audit->log(new AuditEvent(
            action: 'color.created',
            actor: AuditActor::admin($command->actorId),
            subject: "color:{$saved->id()}",
            context: ['name' => $saved->name(), 'hex' => $saved->hex()],
        ));

        return $saved;
    }
}
