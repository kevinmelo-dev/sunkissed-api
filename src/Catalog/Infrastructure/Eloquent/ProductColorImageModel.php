<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $product_id
 * @property int $color_id
 * @property string $storage_key
 * @property int $position
 */
final class ProductColorImageModel extends Model
{
    protected $table = 'product_color_images';

    protected $fillable = ['product_id', 'color_id', 'storage_key', 'position'];
}
