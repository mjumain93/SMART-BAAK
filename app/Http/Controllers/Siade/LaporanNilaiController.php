<?php

namespace App\Http\Controllers\Siade;

use App\Http\Controllers\Controller;
use App\Services\KrsService;
use App\Services\NeofeederService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LaporanNilaiController extends Controller
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
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $prodiJson = $this->neofeeder->getProdi();
        $prodiArray = $prodiJson->getData(true);
        if ($prodiArray['error_code'] !== 0) {
            return $prodiJson;
        }
        $data['prodi'] = $prodiArray['data'];
        $data['tahun_akademik'] = $this->krsService->getTahuAkademik();
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
                    $btn .= '<a target="_blank" href="' . route('laporan-nilai.show', Crypt::encrypt($row->id)) . '">' . $row->NamaDosen . '</a>';
                    return $btn;
                })
                ->rawColumns(['jadwal', 'JumlahMahasiswa1', 'NamaDosen'])
                ->make(true);
        };
        return view('siade.LaporanInputNilai', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $id = Crypt::decrypt($request->laporan_nilai);
        } catch (\Throwable $th) {
            return redirect()->route('laporan-nilai.index');
        }

        $data['jadwal'] = DB::connection('mariadb')
            ->table('jadwal_kuliah')
            ->where('id', $id)
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
                ->where('a.id_jadwal', $id)
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if ($request->ajax()) {
            $cek_jadwal = DB::connection('mariadb')
                ->table('jadwal_kuliah')
                ->where('id', Crypt::decrypt($id))
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
                            ['id_jadwal', '=', Crypt::decrypt($id)],
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
