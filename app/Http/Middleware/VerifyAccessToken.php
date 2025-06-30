<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
        // $token = session('token');

        // if (!$token) {
        //     return redirect()->route('login')->withErrors(['auth' => 'Token tidak ditemukan']);
        // }

        // $response = Http::withToken($token)
        //     ->acceptJson()
        //     ->get('https://sso.umjambi.ac.id/api/me');

        // if ($response->unauthorized()) {
        //     session()->forget('token');
        //     return redirect()->route('login')->withErrors(['auth' => 'Token tidak valid']);
        // }

        // $userData = $response->json('data');

        // // Cari atau buat user lokal berdasarkan email
        // $localUser = User::firstOrCreate(
        //     ['email' => $userData['email']],
        //     ['name' => $userData['name'], 'password' => Hash::make('usususus')]
        // );
        // Auth::login($localUser);

        // // Inject user lokal ke request
        // $request->merge(['user' => $userData, 'auth_user' => $localUser]);

        return $next($request);
    }
}
