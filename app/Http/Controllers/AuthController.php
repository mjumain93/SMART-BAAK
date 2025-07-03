<?php

namespace App\Http\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected $ssoUrl;
    public function __construct()
    {
        $this->ssoUrl = 'https://sso.umjambi.ac.id/login?redirect_uri=' . urlencode('https://smart.umjambi.ac.id/callback');
    }
    public function showLoginForm()
    {
        return redirect($this->ssoUrl);
    }
    public function showRegisterForm()
    {
        return view('auth.register');
    }
    public function callback(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect('/login')->with('error', 'Token tidak ditemukan');
        }

        try {
            $response = Http::withToken($token)->get('https://sso.umjambi.ac.id/me');
            if ($response->ok()) {
                $user = $response->json()['user'];

                $auth = User::firstOrCreate(
                    [
                        'email' => $user['email_pribadi'],
                    ],
                    [
                        'name' => $user['nama_lengkap'],
                        'password' => Hash::make(Str::random(16))
                    ]
                );

                session(['token' => $token]);
                Auth::guard('web')->login($auth);

                return redirect('/home');
            } else {
                return redirect('/login')->with('error', 'Gagal mengambil data pengguna dari SSO');
            }
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Token tidak valid atau sudah kedaluwarsa');
        }
    }

    public function logout()
    {
        Session::flush();
        Auth::logout();
        return redirect('/login');
    }
}
