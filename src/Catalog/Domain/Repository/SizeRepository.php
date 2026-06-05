<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Repository;

use Src\Catalog\Domain\Entity\Size;

interface SizeRepository
{
    public function find(int $id): ?Size;

    /** @return Size[] ordered by sortOrder ascending */
    public function all(bool $onlyActive = false): array;

    public function existsByName(string $name, ?int $excludeId = null): bool;

    public function save(Size $size): Size;
}
