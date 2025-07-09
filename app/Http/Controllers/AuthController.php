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
        $this->ssoUrl = env('SSO_BASE_URL') . '/login?redirect_uri=' . urlencode('http://localhost:8000/callback');
    }
    public function showLoginForm()
    {
        return redirect($this->ssoUrl);
    }
    public function callback(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return redirect('/login')->with('error', 'Token tidak ditemukan');
        }

        try {
            $response = Http::withToken($token)->get(env('SSO_BASE_URL') . '/me');
            if ($response->ok()) {

                $user = $response->json()['user'];
                $isFirstUser = User::count() === 0;
                $auth = User::firstOrCreate(
                    [
                        'email' => $user['nik'],
                    ],
                    [
                        'name' => $user['nama_lengkap'],
                        'password' => Hash::make(Str::random(16))
                    ]
                );
                if ($auth->wasRecentlyCreated && $isFirstUser) {
                    $auth->assignRole('superadmin');
                }

                if ($user['id_tipe'] === 2) {
                    $auth->assignRole('dosen');
                }

                session(['sso_token' => $token]);
                Auth::guard('web')->login($auth);

                return redirect('/home');
            } else {
                return redirect('/login')->with('error', 'Gagal mengambil data pengguna dari SSO');
            }
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Token tidak valid atau sudah kedaluwarsa');
        }
    }

    public function logout(Request $request)
    {
        $token = Session::get('sso_token');

        if ($token) {
            try {
                Http::withToken($token)->post(env('SSO_BASE_URL') . '/logout');
            } catch (\Exception $e) {
            }
        }

        Auth::logout();
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('message', 'Logout berhasil');
    }
}
