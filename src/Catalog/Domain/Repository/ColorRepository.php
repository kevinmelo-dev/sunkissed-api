<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Repository;

use Src\Catalog\Domain\Entity\Color;

interface ColorRepository
{
    public function find(int $id): ?Color;

    /** @return Color[] */
    public function all(): array;

    public function save(Color $color): Color;
}
