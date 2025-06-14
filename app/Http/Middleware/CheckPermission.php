<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $routeName = Route::currentRouteName();

        if ($user && $user->hasRole('superadmin')) {
            return $next($request);
        }

        $menu = Menu::where('route', $routeName)->first();

        if ($menu && $menu->permission) {
            if (!$user || !$user->can($menu->permission)) {
                abort(403, 'Anda tidak memiliki hak akses');
            }
        }
        return $next($request);
    }
}
