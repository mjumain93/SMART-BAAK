<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return Socialite::driver('keycloak')->redirect();
    }
    public function callback()
    {
        $socialiteUser  = Socialite::driver('keycloak')->user();
        session(['keycloak_access_token' => $socialiteUser->token]);
        session(['keycloak_refresh_token' => $socialiteUser->refreshToken]);
        session(['keycloak_id_token' => $socialiteUser->accessTokenResponseBody['id_token'] ?? null]);
        $user = User::where('email', $socialiteUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'password' => '1234'
            ]);
        }
        Auth::login($user);
        return redirect('/home');
    }
    public function showRegisterForm()
    {
        return view('auth.register');
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
