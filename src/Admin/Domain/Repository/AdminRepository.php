<?php

declare(strict_types=1);

namespace Src\Admin\Domain\Repository;

use Src\Admin\Domain\Entity\Admin;

interface AdminRepository
{
    public function findByEmail(string $email): ?Admin;

    public function find(int $id): ?Admin;

    public function save(Admin $admin): Admin;
}
