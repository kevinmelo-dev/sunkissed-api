<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Entity;

use Src\Catalog\Domain\Enum\ProductType;

final class Product
{
    /** @var ProductVariant[] */
    private array $variants = [];

    public function __construct(
        private readonly ?int $id,
        private readonly ProductType $type,
        private string $name,
        private string $slug,
        private ?string $description,
        private bool $active,
        private readonly ?int $coverColorId = null,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function type(): ProductType
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function coverColorId(): ?int
    {
        return $this->coverColorId;
    }

    /** @return ProductVariant[] */
    public function variants(): array
    {
        return $this->variants;
    }

    public function addVariant(ProductVariant $variant): void
    {
        $this->variants[] = $variant;
    }
}
