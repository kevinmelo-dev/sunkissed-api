<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ListColors;

use Src\Catalog\Domain\Entity\Color;
use Src\Catalog\Domain\Repository\ColorRepository;

final class ListColors
{
    public function __construct(
        private readonly ColorRepository $colors,
    ) {}

    /** @return Color[] */
    public function execute(ListColorsQuery $query): array
    {
        return $this->colors->all($query->onlyActive);
    }
}
