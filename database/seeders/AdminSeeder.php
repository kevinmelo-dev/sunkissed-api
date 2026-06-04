<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Admin\Infrastructure\Eloquent\AdminModel;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        AdminModel::create([
            'name' => 'Admin Dev',
            'email' => 'admin@sunkissed.dev',
            'password' => 'password',
            'active' => true,
        ]);

        $this->command->info('Admin criado: admin@sunkissed.dev / password');
    }
}
