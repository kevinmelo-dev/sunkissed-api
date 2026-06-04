<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Auth;

use Src\Admin\Application\Port\AdminTokenIssuer;
use Src\Admin\Infrastructure\Eloquent\AdminModel;

final class SanctumAdminTokenIssuer implements AdminTokenIssuer
{
    public function issue(int $adminId): string
    {
        /** @var AdminModel $model */
        $model = AdminModel::findOrFail($adminId);

        return $model->createToken('admin-token')->plainTextToken;
    }
}
