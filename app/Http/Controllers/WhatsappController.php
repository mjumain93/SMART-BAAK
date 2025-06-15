<?php

namespace App\Http\Controllers;

use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WhatsappController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsappService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }
    public function kirimPesan(Request $request)
    {
        Carbon::setLocale('id');
        $pegawai = DB::connection('mariadb')
            ->table('pegawai as a')
            ->pluck('hp', 'id')
            ->map(function ($hp) {
                $hp = preg_replace('/\D/', '', $hp);
                if (Str::startsWith($hp, '0')) {
                    return '62' . substr($hp, 1);
                }
                return $hp;
            });

        if ($request->ajax()) {
            if (!$request->filled('id')) {
                return response()->json([
                    'error_code' => 1,
                    'error_desc' => '',
                    'message' => 'Jadwal tidak valid',
                    'data' => null
                ], 422);
            };

            $total_mahasiswa = DB::connection('mariadb')
                ->table('mhsw_krs as a')
                ->select('a.id_jadwal', DB::raw('COUNT(*) as total'))
                ->where('a.NA', 'A')
                ->groupBy('a.id_jadwal')
                ->pluck('total', 'id_jadwal');
            $total_diinput = DB::connection('mariadb')
                ->table('mhsw_krs as a')
                ->select('a.id_jadwal', DB::raw('COUNT(*) as total'))
                ->where('a.NA', 'A')
                ->where('a.nilai_angka', '>', 0)
                ->groupBy('a.id_jadwal')
                ->pluck('total', 'id_jadwal');
            $mahasiswa = $total_mahasiswa[Crypt::decrypt($request->id)] ?? 0;
            $diinput = $total_diinput[Crypt::decrypt($request->id)] ?? 0;

            $cek_jadwal = DB::connection('mariadb')
                ->table('jadwal_kuliah')
                ->where('id', Crypt::decrypt($request->id))
                ->first();

            if (!isset($cek_jadwal)) {
                return response()->json([
                    'error_code' => 1,
                    'error_desc' => '',
                    'message' => 'Jadwal tidak valid',
                    'data' => null
                ], 422);
            }

            // $phone = $pegawai[$cek_jadwal->DosenID] ?? null;
            $phone = $pegawai[18] ?? null;
            // $phone = '120363321879606190@g.us';

            if (!isset($phone)) {
                return response()->json([
                    'error_code' => 1,
                    'error_desc' => '',
                    'message' => 'Nomor Handphone tidak valid',
                    'data' => null
                ], 422);
            }

            $message = '';
            $message .= "*Assalamu'alaikum warahmatullahi wabarakatuh,* \n\n";
            $message .= "Berikut kami sampaikan rekapitulasi penginputan nilai perkuliahan *Tahun Akademik " . $cek_jadwal->TahunID . "* : \n\n";
            $message .= 'Nama Dosen : *' . $cek_jadwal->NamaDosen . "* \n";
            $message .= 'Program Studi : *' . $cek_jadwal->ProdiNama . "* \n";
            $message .= 'Kelas : *' . $cek_jadwal->NamaKelas . "* \n";
            $message .= 'Ruang : *' . $cek_jadwal->NamaRuang . "* \n";
            $message .= 'Pukul : *' . $cek_jadwal->jam_mulai . '-' . $cek_jadwal->jam_selesai . " WIB* \n";
            $message .= 'Mahasiswa Dikelas : *' . $mahasiswa . " Orang* \n";
            $message .= 'Mahasiswa Dinilai/Minimal C : *' . $diinput . " Orang* \n\n";
            if ($mahasiswa !== $diinput) {
                $message .= "_Mohon kesediaan Bapak/Ibu Dosen untuk memverifikasi kembali kebenaran data nilai yang telah diinput._\n";
                $message .= "_Apabila data telah sesuai, mohon abaikan pesan ini._\n\n";
            }
            $message .= "Demikian informasi ini kami sampaikan.\n";
            $message .= "Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.\n\n";
            $message .= "*Wassalamu’alaikum warahmatullahi wabarakatuh.*\n\n";
            $message .= "*Pesan ini dikirim otomatis oleh BAAK pada " . Carbon::now()->translatedFormat('d F Y H:i') . " WIB*";

            $result = $this->whatsapp->sendMessage($phone, $message);

            if ($result && $result['status'] == true) {
                return response()->json([
                    'error_code' => 0,
                    'error_desc' => '',
                    'message' => 'Pesan tekirim kepada ' . $cek_jadwal->NamaDosen,
                    'data' => null
                ], 200);
            }

            return response()->json([
                'error_code' => 1,
                'error_desc' => '',
                'message' => $result['reason'],
                'data' => null
            ], 404);
        }
    }
}
