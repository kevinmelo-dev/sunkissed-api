<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Auth;

use Illuminate\Support\Facades\Hash;
use Src\Admin\Application\Port\PasswordVerifier;

final class LaravelPasswordVerifier implements PasswordVerifier
{
    public function verify(string $plain, string $hashed): bool
    {
        return Hash::check($plain, $hashed);
    }
}
