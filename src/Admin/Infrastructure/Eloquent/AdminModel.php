<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Eloquent;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property bool $active
 */
final class AdminModel extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'admins';

    protected $fillable = ['name', 'email', 'password', 'active'];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
        'active' => 'boolean',
    ];
}
