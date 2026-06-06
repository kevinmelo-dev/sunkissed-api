<?php

declare(strict_types=1);

namespace Src\Catalog\Application\GetPublicProduct;

use Src\Catalog\Application\ImageStorage\ImageStorage;
use Src\Catalog\Application\ProductCoverImage\ProductCoverImageResolver;
use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Catalog\Domain\Repository\ProductCategoryRepository;
use Src\Catalog\Domain\Repository\ProductColorImageRepository;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Catalog\Domain\Repository\SizeRepository;
use Src\Catalog\Domain\Service\StockLedger;

final class GetPublicProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductVariantRepository $variants,
        private readonly ProductCategoryRepository $productCategories,
        private readonly ColorRepository $colors,
        private readonly SizeRepository $sizes,
        private readonly ProductColorImageRepository $images,
        private readonly ImageStorage $storage,
        private readonly StockLedger $stockLedger,
        private readonly ProductCoverImageResolver $coverResolver,
    ) {}

    public function execute(GetPublicProductQuery $query): PublicProductDetail
    {
        $product = $this->products->findBySlug($query->slug);

        if ($product === null || ! $product->active()) {
            throw new ProductNotFoundException(0);
        }

        $coverImageUrl = $this->coverResolver->resolve($product);

        $categories = $this->productCategories->categoriesForProduct($product->id());
        $activeCategories = array_filter($categories, fn ($c) => $c->active());

        $allVariants = $this->variants->findForProduct($product->id());
        $activeVariants = array_filter($allVariants, fn (ProductVariant $v) => $v->active());

        $allImages = $this->images->listForProduct($product->id());

        $sizesById = [];
        foreach ($this->sizes->all(onlyActive: true) as $size) {
            $sizesById[$size->id()] = $size;
        }

        $variantsByColor = [];
        foreach ($activeVariants as $variant) {
            $variantsByColor[$variant->colorId()][] = $variant;
        }

        $colorEntries = [];
        foreach ($variantsByColor as $colorId => $colorVariants) {
            $color = $this->colors->find($colorId);

            if ($color === null || ! $color->active()) {
                continue;
            }

            $images = [];
            foreach ($allImages[$colorId] ?? [] as $img) {
                $images[] = [
                    'id' => $img->id(),
                    'position' => $img->position(),
                    'url' => $this->storage->url($img->storageKey()),
                ];
            }

            $sizes = [];
            foreach ($colorVariants as $variant) {
                $size = $sizesById[$variant->sizeId()] ?? null;

                if ($size === null) {
                    continue;
                }

                $balance = $this->stockLedger->availableFor($variant->id());
                $sizes[] = [
                    'id' => $size->id(),
                    'name' => $size->name(),
                    'sort_order' => $size->sortOrder(),
                    'variant_id' => $variant->id(),
                    'sku' => $variant->sku()->value,
                    'price_cents' => $variant->price()->cents,
                    'available' => $balance->available > 0,
                ];
            }

            usort($sizes, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);

            $colorEntries[] = new PublicColorEntry(
                color: $color,
                images: $images,
                sizes: $sizes,
            );
        }

        return new PublicProductDetail(
            product: $product,
            coverImageUrl: $coverImageUrl,
            categories: array_values($activeCategories),
            colors: $colorEntries,
        );
    }
}
