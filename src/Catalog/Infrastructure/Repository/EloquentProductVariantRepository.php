<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Repository;

use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Catalog\Domain\ValueObject\Sku;
use Src\Catalog\Infrastructure\Eloquent\ProductVariantModel;
use Src\Shared\Domain\ValueObject\Money;

final class EloquentProductVariantRepository implements ProductVariantRepository
{
    public function find(int $id): ?ProductVariant
    {
        $model = ProductVariantModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findBySku(Sku $sku): ?ProductVariant
    {
        $model = ProductVariantModel::where('sku', $sku->value)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findCombination(int $productId, int $colorId, int $sizeId): ?ProductVariant
    {
        $model = ProductVariantModel::where('product_id', $productId)
            ->where('color_id', $colorId)
            ->where('size_id', $sizeId)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function existsCombination(int $productId, int $colorId, int $sizeId): bool
    {
        return ProductVariantModel::where('product_id', $productId)
            ->where('color_id', $colorId)
            ->where('size_id', $sizeId)
            ->exists();
    }

    public function findForProduct(int $productId): array
    {
        return ProductVariantModel::where('product_id', $productId)
            ->get()
            ->map(fn (ProductVariantModel $m) => $this->toEntity($m))
            ->all();
    }

    public function findActiveForProductColor(int $productId, int $colorId): array
    {
        return ProductVariantModel::where('product_id', $productId)
            ->where('color_id', $colorId)
            ->where('active', true)
            ->get()
            ->map(fn (ProductVariantModel $m) => $this->toEntity($m))
            ->all();
    }

    public function existsColorForProduct(int $productId, int $colorId): bool
    {
        return ProductVariantModel::where('product_id', $productId)
            ->where('color_id', $colorId)
            ->exists();
    }

    public function save(ProductVariant $variant): ProductVariant
    {
        if ($variant->id() !== null) {
            $model = ProductVariantModel::findOrFail($variant->id());
        } else {
            $model = new ProductVariantModel;
        }

        $model->fill([
            'product_id' => $variant->productId(),
            'color_id' => $variant->colorId(),
            'size_id' => $variant->sizeId(),
            'sku' => $variant->sku()->value,
            'price_cents' => $variant->price()->cents,
            'active' => $variant->active(),
        ])->save();

        return $this->toEntity($model);
    }

    private function toEntity(ProductVariantModel $model): ProductVariant
    {
        return new ProductVariant(
            id: $model->id,
            productId: $model->product_id,
            colorId: $model->color_id,
            sizeId: $model->size_id,
            sku: new Sku($model->sku),
            price: Money::fromCents($model->price_cents),
            active: $model->active,
        );
    }
}
