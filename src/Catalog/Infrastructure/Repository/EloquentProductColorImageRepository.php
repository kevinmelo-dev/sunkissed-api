<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Repository;

use Src\Catalog\Domain\Entity\ProductColorImage;
use Src\Catalog\Domain\Repository\ProductColorImageRepository;
use Src\Catalog\Infrastructure\Eloquent\ProductColorImageModel;

final class EloquentProductColorImageRepository implements ProductColorImageRepository
{
    public function find(int $id): ?ProductColorImage
    {
        $model = ProductColorImageModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function listForProductColor(int $productId, int $colorId): array
    {
        return ProductColorImageModel::where('product_id', $productId)
            ->where('color_id', $colorId)
            ->orderBy('position')
            ->get()
            ->map(fn (ProductColorImageModel $m) => $this->toEntity($m))
            ->all();
    }

    public function listForProduct(int $productId): array
    {
        $models = ProductColorImageModel::where('product_id', $productId)
            ->orderBy('color_id')
            ->orderBy('position')
            ->get();

        $grouped = [];
        foreach ($models as $model) {
            $grouped[$model->color_id][] = $this->toEntity($model);
        }

        return $grouped;
    }

    public function nextPosition(int $productId, int $colorId): int
    {
        $max = ProductColorImageModel::where('product_id', $productId)
            ->where('color_id', $colorId)
            ->max('position');

        return $max === null ? 1 : (int) $max + 1;
    }

    public function save(ProductColorImage $image): ProductColorImage
    {
        if ($image->id() !== null) {
            $model = ProductColorImageModel::findOrFail($image->id());
        } else {
            $model = new ProductColorImageModel;
        }

        $model->fill([
            'product_id' => $image->productId(),
            'color_id' => $image->colorId(),
            'storage_key' => $image->storageKey(),
            'position' => $image->position(),
        ])->save();

        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        ProductColorImageModel::where('id', $id)->delete();
    }

    public function saveOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            ProductColorImageModel::where('id', $id)->update(['position' => $position + 1]);
        }
    }

    private function toEntity(ProductColorImageModel $model): ProductColorImage
    {
        return new ProductColorImage(
            id: $model->id,
            productId: $model->product_id,
            colorId: $model->color_id,
            storageKey: $model->storage_key,
            position: $model->position,
        );
    }
}
