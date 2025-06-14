<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return Socialite::driver('keycloak')->redirect();
    }

    public function attemptLogin()
    {
        $response = Socialite::driver('keycloak')->user();

        $accessToken  = $response->accessTokenResponseBody['id_token'] ?? null;
        $refreshToken = $response->refreshToken ?? null;

        $user = User::firstOrCreate(
            ['email' => $response->getEmail()],
            [
                'name' => $response->getName(),
                'password' => Hash::make(Str::random(16)),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $user->assignRole('user-biasa');
        }

        if ($user && $accessToken && $refreshToken) {
            Auth::login($user);

            // Simpan cookie access & refresh token
            Cookie::queue(cookie(
                'access_token',
                $accessToken,
                5, // 5 menit
                null,
                null,
                true,
                true,
                false,
                'Strict'
            ));

            Cookie::queue(cookie(
                'refresh_token',
                $refreshToken,
                60 * 24 * 7, // 7 hari
                null,
                null,
                true,
                true,
                false,
                'Strict'
            ));
        }

        return redirect('/home');
    }

    public function logout()
    {
        $idToken = request()->cookie('access_token');

        Auth::logout();
        Session::flush();

        // Hapus cookie dengan meng-queue expired version
        Cookie::queue(Cookie::forget('access_token'));
        Cookie::queue(Cookie::forget('refresh_token'));

        $redirectUri = urlencode('http://127.0.0.1:9001');
        $logoutUrl = "https://sso.umjambi.ac.id/realms/sso/protocol/openid-connect/logout?post_logout_redirect_uri={$redirectUri}";

        if ($idToken) {
            $logoutUrl .= "&id_token_hint={$idToken}";
        }

        return redirect()->away($logoutUrl);
    }
}
