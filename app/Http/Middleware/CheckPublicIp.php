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
    protected $allowedIps = ['103.213.116.98'];
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = file_get_contents('https://api.ipify.org');;

        if (!in_array($clientIp, $this->allowedIps)) {
            // if ($request->expectsJson()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Akses ditolak: IP Anda tidak diizinkan.'
            //     ], 403);
            // }
            abort(403, 'IP ' . $clientIp . ' tidak memiliki hak akses');
        }

        return $next($request);
    }
}
