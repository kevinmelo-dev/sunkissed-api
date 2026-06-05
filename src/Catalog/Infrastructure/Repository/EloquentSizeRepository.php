<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Repository;

use Src\Catalog\Domain\Entity\Size;
use Src\Catalog\Domain\Repository\SizeRepository;
use Src\Catalog\Infrastructure\Eloquent\SizeModel;

final class EloquentSizeRepository implements SizeRepository
{
    public function find(int $id): ?Size
    {
        $model = SizeModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function all(bool $onlyActive = false): array
    {
        $query = SizeModel::orderBy('sort_order');

        if ($onlyActive) {
            $query->where('active', true);
        }

        return $query->get()
            ->map(fn (SizeModel $m) => $this->toEntity($m))
            ->all();
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $query = SizeModel::where('name', $name);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function save(Size $size): Size
    {
        if ($size->id() !== null) {
            $model = SizeModel::findOrFail($size->id());
        } else {
            $model = new SizeModel;
        }

        $model->fill([
            'name' => $size->name(),
            'sort_order' => $size->sortOrder(),
            'active' => $size->active(),
        ])->save();

        return $this->toEntity($model);
    }

    private function toEntity(SizeModel $model): Size
    {
        return new Size(
            id: $model->id,
            name: $model->name,
            sortOrder: $model->sort_order,
            active: $model->active,
        );
    }
}
