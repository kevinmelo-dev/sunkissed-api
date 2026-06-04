<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Repository;

use Src\Admin\Domain\Entity\Admin;
use Src\Admin\Domain\Repository\AdminRepository;
use Src\Admin\Infrastructure\Eloquent\AdminModel;

final class EloquentAdminRepository implements AdminRepository
{
    public function findByEmail(string $email): ?Admin
    {
        $model = AdminModel::where('email', $email)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function find(int $id): ?Admin
    {
        $model = AdminModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function save(Admin $admin): Admin
    {
        if ($admin->id() !== null) {
            $model = AdminModel::findOrFail($admin->id());
        } else {
            $model = new AdminModel;
        }

        $model->fill([
            'name' => $admin->name(),
            'email' => $admin->email(),
            'password' => $admin->password(),
            'active' => $admin->active(),
        ])->save();

        return $this->toEntity($model);
    }

    private function toEntity(AdminModel $model): Admin
    {
        return new Admin(
            id: $model->id,
            name: $model->name,
            email: $model->email,
            password: $model->password,
            active: $model->active,
        );
    }
}
