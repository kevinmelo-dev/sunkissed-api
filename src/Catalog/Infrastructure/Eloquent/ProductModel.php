<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $active
 */
final class ProductModel extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = ['type', 'name', 'slug', 'description', 'active'];

    protected $casts = ['active' => 'boolean'];
}
