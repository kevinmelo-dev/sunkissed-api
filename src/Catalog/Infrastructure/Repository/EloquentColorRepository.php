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

    public function all(bool $onlyActive = false): array
    {
        $query = ColorModel::query();

        if ($onlyActive) {
            $query->where('active', true);
        }

        return $query->get()
            ->map(fn (ColorModel $m) => $this->toEntity($m))
            ->all();
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $query = ColorModel::where('name', $name);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
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
