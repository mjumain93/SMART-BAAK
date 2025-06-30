<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
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
        $accessToken = Session::get('access_token');

        if (!$accessToken) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            $key = new Key(env('JWT_SECRET'), 'HS256');
            $decoded = JWT::decode($accessToken, $key);

            $exp = $decoded->exp;

            if ($exp < time()) {
                return response()->json(['error' => 'Token has expired'], 401);
            }

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }
}
