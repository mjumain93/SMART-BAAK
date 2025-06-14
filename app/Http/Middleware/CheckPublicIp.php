<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPublicIp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $envIps = env('ALLOWED_IPS');
        if (is_null($envIps) || trim($envIps) === '') {
            return $next($request);
        }
        $allowedIps = array_map('trim', explode(',', $envIps));

        $clientIp = file_get_contents('https://api.ipify.org');;

        if (!in_array($clientIp, $allowedIps)) {
            abort(403, 'IP ' . $clientIp . ' tidak memiliki hak akses');
        }

        return $next($request);
    }
}
