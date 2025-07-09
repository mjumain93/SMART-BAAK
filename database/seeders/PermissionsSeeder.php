<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = Role::create(['name' => 'superadmin']);
        $userbiasa = Role::create(['name' => 'user-biasa']);
        $permissions = [
            'home',
            'menus.index',
            'menus.create',
            'menus.store',
            'menus.edit',
            'menus.update',
            'menus.destroy',
            'roles.index',
            'roles.create',
            'roles.store',
            'roles.edit',
            'roles.update',
            'roles.destroy',
            'menus.index',
            'menus.create',
            'menus.store',
            'menus.edit',
            'menus.update',
            'menus.destroy',
            'users.index',
            'users.create',
            'users.store',
            'users.edit',
            'users.update',
            'users.destroy',
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::firstOrCreate(['name' => $permission]);
            $superadmin->givePermissionTo($perm);
        }

        $userbiasa->givePermissionTo('home');
    }
}
