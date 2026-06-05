<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Repository;

use Src\Catalog\Domain\Entity\Color;

interface ColorRepository
{
    public function find(int $id): ?Color;

    /** @return Color[] */
    public function all(bool $onlyActive = false): array;

    public function existsByName(string $name, ?int $excludeId = null): bool;

    public function save(Color $color): Color;
}
