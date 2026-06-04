<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Repository;

use Src\Catalog\Domain\Entity\Color;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Catalog\Infrastructure\Eloquent\ColorModel;

final class EloquentColorRepository implements ColorRepository
{
    public function find(int $id): ?Color
    {
        $model = ColorModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function all(): array
    {
        return ColorModel::where('active', true)
            ->get()
            ->map(fn (ColorModel $m) => $this->toEntity($m))
            ->all();
    }

    public function save(Color $color): Color
    {
        if ($color->id() !== null) {
            $model = ColorModel::findOrFail($color->id());
        } else {
            $model = new ColorModel;
        }

        $model->fill([
            'name' => $color->name(),
            'hex' => $color->hex(),
            'active' => $color->active(),
        ])->save();

        return $this->toEntity($model);
    }

    private function toEntity(ColorModel $model): Color
    {
        return new Color(
            id: $model->id,
            name: $model->name,
            hex: $model->hex,
            active: $model->active,
        );
    }
}
