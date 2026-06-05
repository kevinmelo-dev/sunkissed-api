<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Entity;

use Src\Catalog\Domain\Exception\InvalidCategoryHierarchyException;

final class Category
{
    private function __construct(
        private readonly ?int $id,
        private string $name,
        private string $slug,
        private ?int $parentId,
        private bool $active,
    ) {}

    public static function createRoot(?int $id, string $name, string $slug, bool $active = true): self
    {
        return new self($id, $name, $slug, null, $active);
    }

    public static function createChild(?int $id, string $name, string $slug, self $parent, bool $active = true): self
    {
        if (! $parent->isRoot()) {
            throw new InvalidCategoryHierarchyException;
        }

        return new self($id, $name, $slug, $parent->id(), $active);
    }

    public static function reconstitute(?int $id, string $name, string $slug, ?int $parentId, bool $active): self
    {
        return new self($id, $name, $slug, $parentId, $active);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function parentId(): ?int
    {
        return $this->parentId;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }
}
