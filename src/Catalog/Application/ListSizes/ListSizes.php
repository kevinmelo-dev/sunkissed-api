<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListSizes;

use Src\Catalog\Domain\Entity\Size;
use Src\Catalog\Domain\Repository\SizeRepository;

final class ListSizes
{
    public function __construct(
        private readonly SizeRepository $sizes,
    ) {}

    /** @return Size[] */
    public function execute(ListSizesQuery $query): array
    {
        return $this->sizes->all($query->onlyActive);
    }
}
