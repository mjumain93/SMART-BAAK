<?php

namespace App\Http\Controllers\Siade;

use App\Http\Controllers\Controller;
use App\Services\KrsService;
use App\Services\NeofeederService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class KrsController extends Controller
{
    protected $neofeeder;
    protected $krsService;
    protected $whatsapp;

    public function __construct(NeofeederService $neofeeder, KrsService $krsService, WhatsappService $whatsapp)
    {
        $this->neofeeder = $neofeeder;
        $this->krsService = $krsService;
        $this->whatsapp = $whatsapp;
    }

    private function tahun_akademik()
    {
        $tahunMulai = 2020;
        $tahunAkhir = Carbon::now()->format('Y');

        $tahun_angkatan = [];

        for ($i = $tahunMulai; $i <= $tahunAkhir; $i++) {
            $tahun_angkatan[] = $i . '1'; // Semester Ganjil
            $tahun_angkatan[] = $i . '2'; // Semester Genap
        }
        return $tahun_angkatan;
    }
    public function LaporanNilaiSemester(Request $request)
    {
        $prodiJson = $this->neofeeder->getProdi();
        $prodiArray = $prodiJson->getData(true);
        if ($prodiArray['error_code'] !== 0) {
            return $prodiJson;
        }
        $data['prodi'] = $prodiArray['data'];
        $data['tahun_akademik'] = $this->tahun_akademik();
        if ($request->ajax()) {
            if (!$request->filled('program_studi') || !$request->filled('tahun_akademik')) {
                return DataTables::of([])->addIndexColumn()->make(true);
            }

            $query_jadwal = DB::connection('mariadb')
                ->table('jadwal_kuliah')
                ->select([
                    'id',
                    'HariNama',
                    'jam_mulai',
                    'jam_selesai',
                    'NamaKelas',
                    'Kelompok',
                    'NamaKurikulum',
                    'Semester',
                    'MkKode',
                    'NamaID',
                    'NamaEn',
                    'sks',
                    'NamaDosen',
                    'NamaRuang',
                    'WajibDosen',
                    'ProdiID',
                    'ProdiNama'
                ])
                ->where('TahunID', $request->tahun_akademik)
                ->where('ProdiID', $request->program_studi)
                ->orderBy('NamaDosen');
            $result = $query_jadwal->get();

            $jumlahMahasiswa = DB::connection('mariadb')
                ->table('mhsw_krs as a')
                ->select('a.id_jadwal', DB::raw('COUNT(*) as total'))
                ->where('a.NA', 'A')
                ->groupBy('a.id_jadwal')
                ->pluck('total', 'id_jadwal');
            $jumlahMahasiswa1 = DB::connection('mariadb')
                ->table('mhsw_krs as a')
                ->select('a.id_jadwal', DB::raw('COUNT(*) as total'))
                ->where('a.NA', 'A')
                ->where('a.nilai_angka', '>', 0)
                ->groupBy('a.id_jadwal')
                ->pluck('total', 'id_jadwal');

            return DataTables::of($result)
                ->addIndexColumn()
                ->addColumn('jadwal', function ($row) {
                    return  $row->NamaRuang . '/' . $row->NamaKelas .  '</br>' . $row->HariNama . '/' . $row->jam_mulai . '-' . $row->jam_selesai;
                })
                ->addColumn('JumlahMahasiswa1', function ($row) use ($jumlahMahasiswa1, $jumlahMahasiswa) {
                    $jumlah1 = $jumlahMahasiswa1[$row->id] ?? 0;
                    $jumlah = $jumlahMahasiswa[$row->id] ?? 0;
                    return $jumlah1 . '/' . $jumlah;
                })
                ->editColumn('NamaDosen', function ($row) {
                    $btn = '';
                    $btn .= '<a target="_blank" href="' . route('siade.detailnilaimahasiswa', Crypt::encrypt($row->id)) . '">' . $row->NamaDosen . '</a>';
                    return $btn;
                })
                ->rawColumns(['jadwal', 'JumlahMahasiswa1', 'NamaDosen'])
                ->make(true);
        };
        return view('siade.LaporanInputNilai', $data);
    }
    public function KrsMahasiswa(Request $request)
    {
        $prodiJson = $this->neofeeder->getProdi();
        $prodiArray = $prodiJson->getData(true);
        if ($prodiArray['error_code'] !== 0) {
            return $prodiJson;
        }
        $data['prodi'] = $prodiArray['data'];
        $data['tahun_akademik'] = $this->tahun_akademik();
        $data['tahun_angkatan'] = DB::connection('mariadb')
            ->table('mhsw')
            ->select('TahunID')
            ->distinct()
            ->pluck('TahunID');
        if ($request->ajax()) {
            $filters = [
                'b.ProdiID' => $request->program_studi,
                'a.TahunID' => $request->tahun_akademik,
            ];

            return DataTables::of($this->krsService->getKrs($filters))
                ->addIndexColumn()
                ->make(true);
        };
        return view('siade.KrsMahasiswa', $data);
    }
    public function detailNilai(Request $request)
    {
        $data['jadwal'] = DB::connection('mariadb')
            ->table('jadwal_kuliah')
            ->where('id', Crypt::decrypt($request->id))
            ->first();
        if ($request->ajax()) {
            $data_nilai = DB::connection('mariadb')
                ->table('mhsw_krs as a')
                ->join('mhsw as b', 'a.nim', '=', 'b.nim')
                ->select([
                    'b.nama_lengkap',
                    'a.status_pa',
                    'b.nim',
                    'a.nilai_angka',
                    'a.nilai_huruf',
                    'a.lulus',
                    'a.id_jadwal',
                    'b.ProdiID',
                ])
                ->where('a.id_jadwal', Crypt::decrypt($request->id))
                ->where('a.NA', 'A');

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $data_nilai->where('b.nim', 'like', "%{$search}%");
            }
            $data_nilai->orderBy('b.nim', 'asc');

            return DataTables::of($data_nilai)
                ->addIndexColumn()
                ->make(true);
        }

        return view('siade.DetailNilaiMahasiswa', $data);
    }
    public function updateNilai(Request $request)
    {
        if ($request->ajax()) {
            $cek_jadwal = DB::connection('mariadb')
                ->table('jadwal_kuliah')
                ->where('id', Crypt::decrypt($request->id_jadwal))
                ->first();

            // dd($cek_jadwal);

            if (!empty($cek_jadwal)) {
                $cek_nilai = DB::connection('mariadb')
                    ->table('nilai')
                    ->where('NA', 'A')
                    ->where('ProdiID', $cek_jadwal->ProdiID)
                    ->where('nilai_mulai', '<=', $request->nilai_angka)
                    ->where('nilai_sampai', '>=', $request->nilai_angka)
                    ->orderByDesc('bobot')
                    ->first();

                if (!empty($cek_nilai)) {
                    $idDosen     = $cek_jadwal->DosenID;
                    $nilaiAngka  = $request->nilai_angka;
                    $nilaiHuruf  = $cek_nilai->nama;
                    $nilaiBobot  = $cek_nilai->bobot;
                    $lulus       = $cek_nilai->lulus;

                    $update_nilai = DB::connection('mariadb')
                        ->table('mhsw_krs')
                        ->where([
                            ['nim', '=', $request->nim],
                            ['id_jadwal', '=', Crypt::decrypt($request->id_jadwal)],
                        ])
                        ->update([
                            'nilai_angka'           => $nilaiAngka,
                            'nilai_huruf'           => $nilaiHuruf,
                            'nilai_bobot'           => $nilaiBobot,
                            'lulus'                 => $lulus,
                            'id_update_nilai'       => $idDosen,
                            'datetime_update_nilai' => now(),
                        ]);
                    if (!$update_nilai) {
                        return response()->json([
                            'error_code' => 1,
                            'error_desc' => 'Nilai gagal disimpan.',
                            'data' => null
                        ], 422);
                    } else {
                        return response()->json([
                            'error_code' => 0,
                            'error_desc' => '',
                            'message' => 'Nilai berhasil disimpan.',
                            'data' => null
                        ], 200);
                    }
                }
            }
        }
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
            // $phone = $pegawai[18] ?? null;
            $phone = '120363321879606190@g.us';

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
