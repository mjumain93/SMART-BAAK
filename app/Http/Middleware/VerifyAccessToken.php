<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class VerifyAccessToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = Session::get('sso_token');

        if (!$token) {
            return redirect('/login');
        }
        try {
            $decoded = JWT::decode($token, new Key(env('SSO_JWT_SECRET'), 'HS256'));
        } catch (\Firebase\JWT\ExpiredException $e) {
            Auth::logout();
            Session::flush();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login')->withErrors('Session expired, please login again.');
        } catch (\Exception $e) {
            return redirect('/login')->withErrors('Invalid token, please login again.');
        }
        return $next($request);
    }
}
