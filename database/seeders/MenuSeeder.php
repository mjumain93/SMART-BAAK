<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('menus')->truncate();

        $homeId = DB::table('menus')->insertGetId([
            'text' => 'Home',
            'route' => 'home',
            'icon' => 'bx bx-home-alt',
            'permission' => 'home',
            'parent_id' => null,
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pengaturanId = DB::table('menus')->insertGetId([
            'text' => 'Pengaturan',
            'route' => null,
            'icon' => 'bx bx-category',
            'permission' => null,
            'parent_id' => null,
            'order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('menus')->insert([
            [
                'text' => 'Menu',
                'route' => 'menus.index',
                'icon' => null,
                'permission' => 'menus-index',
                'parent_id' => $pengaturanId,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'text' => 'Permissions',
                'route' => 'permissions.index',
                'icon' => null,
                'permission' => 'permissions-index',
                'parent_id' => $pengaturanId,
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'text' => 'Roles',
                'route' => 'roles.index',
                'icon' => null,
                'permission' => 'roles-index',
                'parent_id' => $pengaturanId,
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
