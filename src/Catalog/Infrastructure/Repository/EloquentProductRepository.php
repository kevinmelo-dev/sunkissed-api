<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Repository;

use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Enum\ProductType;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Catalog\Infrastructure\Eloquent\ProductModel;

final class EloquentProductRepository implements ProductRepository
{
    public function find(int $id): ?Product
    {
        $model = ProductModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findBySlug(string $slug): ?Product
    {
        $model = ProductModel::where('slug', $slug)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function save(Product $product): Product
    {
        if ($product->id() !== null) {
            $model = ProductModel::findOrFail($product->id());
        } else {
            $model = new ProductModel;
        }

        $model->fill([
            'type' => $product->type()->value,
            'name' => $product->name(),
            'slug' => $product->slug(),
            'description' => $product->description(),
            'active' => $product->active(),
        ])->save();

        return $this->toEntity($model);
    }

    private function toEntity(ProductModel $model): Product
    {
        return new Product(
            id: $model->id,
            type: ProductType::from($model->type),
            name: $model->name,
            slug: $model->slug,
            description: $model->description,
            active: $model->active,
        );
    }
}
