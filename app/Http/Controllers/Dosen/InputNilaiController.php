<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Services\KrsService;
use App\Services\NeofeederService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Yajra\DataTables\Facades\DataTables;

class InputNilaiController extends Controller
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

            $response = Http::withToken(session()->get('token'))->get('https://sso.umjambi.ac.id/me');
            if ($response->ok()) {
                $DosenID = $response['user']['id'];
            };

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
                ->where('DosenID', $DosenID ?? null)
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
                    $btn .= '<a target="_blank" href="' . route('input-nilai.show', Crypt::encrypt($row->id)) . '">' . $row->NamaDosen . '</a>';
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
    public function show(string $id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
