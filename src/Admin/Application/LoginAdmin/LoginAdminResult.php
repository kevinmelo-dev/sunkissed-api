<?php

declare(strict_types=1);

namespace Src\Admin\Application\LoginAdmin;

final readonly class LoginAdminResult
{
    public function __construct(
        public string $token,
        public int $id,
        public string $name,
        public string $email,
    ) {}
}
