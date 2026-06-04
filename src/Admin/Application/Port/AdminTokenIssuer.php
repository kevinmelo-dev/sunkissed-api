<?php

declare(strict_types=1);

namespace Src\Admin\Application\Port;

interface AdminTokenIssuer
{
    public function issue(int $adminId): string;
}
