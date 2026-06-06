<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ProductCoverImage;

use Src\Catalog\Application\ImageStorage\ImageStorage;
use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Repository\ProductColorImageRepository;

final class ProductCoverImageResolver
{
    public function __construct(
        private readonly ProductColorImageRepository $images,
        private readonly ImageStorage $storage,
    ) {}

    /**
     * Returns the URL of the product's cover image, or null when no image exists.
     *
     * Rule: use the first image (lowest position) of cover_color_id if set and
     * that color has images; otherwise fall back to the first active color (by id)
     * that has at least one image.
     */
    public function resolve(Product $product): ?string
    {
        $all = $this->images->listForProduct($product->id());

        if (empty($all)) {
            return null;
        }

        if ($product->coverColorId() !== null && isset($all[$product->coverColorId()])) {
            $coverImages = $all[$product->coverColorId()];
            if (! empty($coverImages)) {
                return $this->storage->url($coverImages[0]->storageKey());
            }
        }

        // Fallback: first color by id that has images
        ksort($all);
        foreach ($all as $images) {
            if (! empty($images)) {
                return $this->storage->url($images[0]->storageKey());
            }
        }

        return null;
    }
}
