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
    public function showLoginForm()
    {
        return view('auth.login');
    }
    public function showRegisterForm()
    {
        return view('auth.register');
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email atau username wajib diisi.',
            'email.string'   => 'Format email atau username tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.string'   => 'Format password tidak valid.',
        ]);
        $response = Http::post('https://sso.umjambi.ac.id/api/auth/login', [
            'username' => $request->email,
            'password' => $request->password,
        ])->json();

        if ($response['success'] == true) {
            Session::put('access_token', $response['data']['access_token']);
            $accessToken = Session::get('access_token');

            $key = new Key(env('JWT_SECRET'), 'HS256');
            $decoded = JWT::decode($accessToken, $key);

            $user = User::firstOrCreate(
                ['email' => $decoded->nik],
                [
                    'name'     => $decoded->nama_lengkap,
                    'password' => Hash::make(Str::random(16)),
                ]
            );

            if ($user->wasRecentlyCreated) {
                $user->assignRole('superadmin');
            }

            Auth::login($user);

            return redirect()->route('home');
        } else {
            return back()->withErrors(['email' => 'NIDN/NIP tidak ditemukan di sistem.']);
        }
        return redirect()->route('home');
    }
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect('/home');
    }

    public function logout(Request $request)
    {
        $idIoken = session('keycloak_id_token'); // ID Token yang disimpan di sesi

        if (!$idIoken) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            return redirect('/'); // Redirect ke halaman utama atau halaman login
        }
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $keycloakLogoutUrl = env('KEYCLOAK_BASE_URL') . '/realms/' . env('KEYCLOAK_REALM') . '/protocol/openid-connect/logout';
        $redirectUri = urlencode(env('APP_URL')); // Arahkan ke halaman utama aplikasi Laravel setelah logout
        $logoutUrl = $keycloakLogoutUrl . '?id_token_hint=' . $idIoken . '&post_logout_redirect_uri=' . $redirectUri;
        return redirect()->to($logoutUrl);
    }
    private function getUserInfo($accessToken)
    {
        $response = Http::withToken($accessToken)
            ->get(env('KEYCLOAK_BASE_URL') . '/realms/' . env('KEYCLOAK_REALM') . '/protocol/openid-connect/userinfo');

        return $response->json();
    }
}
