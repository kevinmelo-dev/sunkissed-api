<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $product_id
 * @property int $color_id
 * @property int $size_id
 * @property string $sku
 * @property int $price_cents
 * @property string|null $image
 * @property bool $active
 */
final class ProductVariantModel extends Model
{
    protected $table = 'product_variants';

    protected $fillable = ['product_id', 'color_id', 'size_id', 'sku', 'price_cents', 'image', 'active'];

    protected $casts = ['active' => 'boolean'];
}
