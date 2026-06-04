<?php

declare(strict_types=1);

namespace Src\Admin\Application\LoginAdmin;

final readonly class LoginAdminCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
