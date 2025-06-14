<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\Auth;

class MenuService
{
    public function getSidebarMenu()
    {
        $user = Auth::user();

        if (!$user) {
            return collect();
        }

        $menus = Menu::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->orderBy('order');
            }])
            ->orderBy('order')
            ->get()
            ->map(function ($menu) use ($user) {
                // Filter child menu berdasarkan akses user
                $menu->children = $menu->children->filter(function ($child) use ($user) {
                    return $this->canAccess($user, $child);
                });

                return $menu;
            })
            ->filter(function ($menu) use ($user) {
                if ($menu->permission) {
                    // Jika parent punya permission, cek apakah user bisa akses
                    return $this->canAccess($user, $menu);
                }

                // Jika parent tidak punya permission, tampilkan hanya jika ada child yang bisa diakses
                return $menu->children->isNotEmpty();
            });

        return $menus;
    }

    protected function canAccess($user, $menu)
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }
        if (!$menu->permission) {
            return true;
        }

        return $user->can($menu->permission);
    }
}
