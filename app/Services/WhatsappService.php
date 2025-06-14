<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsappService
{
    protected $token;

    public function __construct()
    {
        $this->token = env('TOKEN_WA', 'secret');
    }

    public function sendMessage($phone, $message)
    {
        $response = Http::withHeaders([
            'Authorization' => $this->token,
        ])->post('https://api.fonnte.com/send', [
            'target' => $phone,
            'message' => $message,
            'delay' => '3-10',
            'countryCode' => '62',
        ]);

        return $response->json();
    }
    public function getGroup()
    {
        // $response = Http::withHeaders([
        //     'Authorization' => $this->token,
        // ])->get('https://api.fonnte.com/get-whatsapp-group');

        // return  $response->json();
    }
}
