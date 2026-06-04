<?php

declare(strict_types=1);

namespace Src\Admin\Application\Port;

interface PasswordVerifier
{
    public function verify(string $plain, string $hashed): bool;
}
