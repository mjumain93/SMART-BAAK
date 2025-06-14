<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NeofeederService
{
    private $api_url;
    private $username;
    private $password;

    public function __construct()
    {
        $this->username = env('NEOFEEDER_USERNAME', '12345');
        $this->password = env('NEOFEEDER_PASSWORD', '12345');
        $this->api_url = env('NEOFEEDER_URL', 'http://');
    }

    private function getToken()
    {
        try {
            $response = Http::asJson()->timeout(30)->post($this->api_url, [
                'act' => 'GetToken',
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                $body = $response->json();

                if (isset($body['error_code']) && $body['error_code'] === 0) {
                    return $body['data']['token'] ?? null;
                }
                Log::error('NeofeederService: Gagal mendapatkan token.', ['response' => $body]);
            } else {
                Log::error('NeofeederService: HTTP request gagal.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('NeofeederService: Exception saat request token.', [
                'message' => $e->getMessage()
            ]);
        }

        return null;
    }

    public function getData(array $data)
    {
        $token = $this->getToken();

        if (!$token) {
            return response()->json([
                'error_code' => 1,
                'error_desc' => 'Gagal mendapatkan token dari Neofeeder.',
                'data' => null
            ], 500);
        }

        $postData = array_merge(['token' => $token], $data);

        try {
            $response = Http::asJson()->timeout(30)->post($this->api_url, $postData);
        } catch (\Exception $e) {
            return response()->json([
                'error_code' => 2,
                'error_desc' => 'HTTP request error: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }

        if (!$response->successful()) {
            return response()->json([
                'error_code' => 3,
                'error_desc' => "HTTP error code: " . $response->status(),
                'data' => null
            ], $response->status());
        }

        $responseBody = $response->json();

        if (isset($responseBody['error_code']) && $responseBody['error_code'] !== 0) {
            return response()->json([
                'error_code' => 4,
                'error_desc' => $responseBody['error_desc'] ?? 'Terjadi kesalahan dari API.',
                'data' => null
            ], 500);
        }

        return response()->json([
            'error_code' => 0,
            'error_desc' => '',
            'jumlah' => count($responseBody['data']),
            'data' => $responseBody['data'] ?? null
        ], 200);
    }
    public function getProdi()
    {
        $data = [
            [
                "id_program_studi" => "8acc323f-e11f-4d5f-98ce-0b04d1df9ff8",
                "kode_program_studi" => "60201",
                "nama_program_studi" => "Ekonomi Pembangunan",
                "nama_jenjang_pendidikan" => "S1"
            ],
            [
                "id_program_studi" => "259e7e4a-01a1-477d-bbac-3571d1e7078e",
                "kode_program_studi" => "55201",
                "nama_program_studi" => "Informatika",
                "nama_jenjang_pendidikan" => "S1",
            ],
            [
                "id_program_studi" => "a8f84daf-52fc-49d0-b7f7-db7ea9487887",
                "kode_program_studi" => "54251",
                "nama_program_studi" => "Kehutanan",
                "nama_jenjang_pendidikan" => "S1"
            ],
            [
                "id_program_studi" => "4c44b5bf-fb5f-4264-af31-8545317b7458",
                "kode_program_studi" => "61201",
                "nama_program_studi" => "Manajemen",
                "nama_jenjang_pendidikan" => "S1"
            ],
            [
                "id_program_studi" => "2395d748-f5e6-452c-9887-a48a2cca93ff",
                "kode_program_studi" => "35201",
                "nama_program_studi" => "Perencanaan Wilayah dan Kota",
                "nama_jenjang_pendidikan" => "S1",
            ],
            [
                "id_program_studi" => "d7608ca2-903b-4858-96cd-f201ef3c465f",
                "kode_program_studi" => "57201",
                "nama_program_studi" => "Sistem Informasi",
                "nama_jenjang_pendidikan" => "S1"
            ],
            [
                "id_program_studi" => "5d80e494-dd61-40db-8c55-018c5ce320fa",
                "kode_program_studi" => "60102",
                "nama_program_studi" => "Ekonomi Pembangunan",
                "nama_jenjang_pendidikan" => "S2"
            ]
        ];

        return response()->json([
            'error_code' => 0,
            'error_desc' => '',
            'jumlah' => count($data),
            'data' => $data
        ], 200);
    }
}
