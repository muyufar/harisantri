<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccessSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(config('hsn.permissions'))->map(function (string $name, string $slug) {
            return Permission::query()->updateOrCreate(['slug' => $slug], ['name' => $name]);
        });

        $roles = [];
        foreach (config('hsn.roles') as $slug => $role) {
            $model = Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $role['name']]
            );
            $ids = $role['permissions'] === '*'
                ? $permissions->pluck('id')
                : $permissions->whereIn('slug', $role['permissions'])->pluck('id');
            $model->permissions()->sync($ids);
            $roles[$slug] = $model;
        }

        $password = env('ADMIN_SEED_PASSWORD', 'ChangeMe!HSN2026');
        $accounts = [
            ['Super Admin', 'admin@pcnu-magelang.test', 'super-admin'],
            ['Admin Pendaftaran', 'pendaftaran@pcnu-magelang.test', 'registration-admin'],
            ['Admin Keuangan', 'keuangan@pcnu-magelang.test', 'finance-admin'],
            ['Petugas Lapangan', 'lapangan@pcnu-magelang.test', 'field-officer'],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => $password, 'is_active' => true]
            );
            $user->roles()->sync([$roles[$role]->id]);
        }
    }
}
