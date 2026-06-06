<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $active
 * @property int|null $cover_color_id
 */
final class ProductModel extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = ['type', 'name', 'slug', 'description', 'active', 'cover_color_id'];

    protected $casts = ['active' => 'boolean'];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CategoryModel::class, 'category_product', 'product_id', 'category_id');
    }
}
