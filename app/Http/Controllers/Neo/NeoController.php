<?php

namespace App\Http\Controllers\Neo;

use App\Http\Controllers\Controller;
use App\Services\NeofeederService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class NeoController extends Controller
{
    protected $neofeeder;

    public function __construct(NeofeederService $neofeeder)
    {
        $this->neofeeder = $neofeeder;
    }

    private function tahun_akademik()
    {
        $data = [
            "act" => "GetPeriode",
            "filter" => "",
            "order" => "",
            "limit" => 0,
            "offset" => 0
        ];

        $response = $this->neofeeder->getData($data);
        $responseArray = $response->getData(true);
        if ($responseArray['error_code'] !== 0) {
            return $response;
        }

        $uniqueData = collect($responseArray['data'] ?? [])->unique('periode_pelaporan')->values();
        return response()->json([
            'error_code' => 0,
            'error_desc' => '',
            'jumlah' => $uniqueData->count(),
            'data' => $uniqueData
        ], 200);
    }

    private function kelasPerkuliahan($idProdi = null, $tahun_akademik = null)
    {
        $data = [
            "act" => "GetListKelasKuliah",
            "filter" => "id_prodi='$idProdi' and id_semester='$tahun_akademik'",
            "order" => "",
            "limit" => 0,
            "offset" => 0
        ];

        $response = $this->neofeeder->getData($data);
        $responseArray = $response->getData(true);

        if ($responseArray['error_code'] !== 0) {
            return $response;
        }

        $uniqueData = collect($responseArray['data'] ?? []);

        return response()->json([
            'error_code' => 0,
            'error_desc' => '',
            'jumlah' => $uniqueData->count(),
            'data' => $uniqueData
        ], 200);
    }

    public function exportKrs(Request $request)
    {
        $error = [];
        $prodiJson = $this->neofeeder->getProdi();
        $prodiArray = $prodiJson->getData(true);
        if ($prodiArray['error_code'] !== 0) {
            $error[] = $prodiJson;
        }

        $tahunakademikJson = $this->tahun_akademik();
        $tahunAkademikArray = $tahunakademikJson->getData(true);
        if ($tahunAkademikArray['error_code'] !== 0) {
            $error[] = $tahunakademikJson;
        }

        $data['prodi'] = $prodiArray['data'];
        $data['tahun_akademik'] = $tahunAkademikArray['data'];
        $data['tahun_angkatan'] = DB::connection('mariadb')
            ->table('mhsw')
            ->select('TahunID')
            ->distinct()
            ->pluck('TahunID');

        // dd($error);

        if ($request->ajax()) {
            if (!$request->filled('program_studi') || !$request->filled('tahun_akademik') || !$request->filled('tahun_angkatan') || !$request->filled('kelas_perkuliahan')) {
                return DataTables::of([])->addIndexColumn()->make(true);
            }

            $prodiJson = $this->neofeeder->getProdi();
            $prodiArray = $prodiJson->getData(true);
            if ($prodiArray['error_code'] !== 0) {
                return $prodiJson;
            }

            $collectProgramStudi = collect($prodiArray['data'] ?? []);
            $foundProgramStudi = $collectProgramStudi->firstWhere('kode_program_studi', $request->program_studi);
            $idProgramStudi = $foundProgramStudi['id_program_studi'] ?? null;

            if (!$idProgramStudi) {
                return response()->json([
                    'error_code' => 1,
                    'error_desc' => 'Program studi tidak ditemukan.',
                    'data' => null
                ], 422);
            }

            $kelas = $request->kelas_perkuliahan ?? null;
            if (!$kelas) {
                return response()->json([
                    'error_code' => 1,
                    'error_desc' => 'Tahun angkatan tidak dikenali.',
                    'data' => null
                ], 422);
            }

            $data = [
                "act" => "GetDetailKelasKuliah",
                "filter" => "id_semester='$request->tahun_akademik' and nama_kelas_kuliah='$kelas' and id_prodi='$idProgramStudi'",
                "order" => "",
                "limit" => 0,
                "offset" => 0
            ];

            $kelasJson = $this->neofeeder->getData($data);
            $kelasArray = $kelasJson->getData(true);
            if ($kelasArray['error_code'] !== 0) {
                return $kelasJson;
            }
            $mkNeofeeder = $kelasArray['data'];

            $krsSiade = DB::connection('mariadb')->table('mhsw as a')
                ->join('mhsw_krs as b', 'a.nim', '=', 'b.nim')
                ->join('jadwal as c', 'b.id_jadwal', '=', 'c.id')
                ->join('mk as d', 'c.MkID', '=', 'd.id')
                ->select(
                    'a.nim',
                    'a.nama_lengkap',
                    'a.TahunID as TahunAngkatan',
                    'a.ProdiID',
                    'b.TahunID',
                    'd.MkKode as KodeMK',
                    'd.NamaID as NamaMK'
                )
                ->when($request->filled('program_studi'), fn($q) => $q->where('a.ProdiID', $request->program_studi))
                ->when($request->filled('tahun_akademik'), fn($q) => $q->where('b.TahunID', $request->tahun_akademik))
                ->when($request->filled('tahun_angkatan'), fn($q) => $q->where('a.TahunID', $request->tahun_angkatan))
                ->where('b.NA', 'A')
                ->orderBy('a.nim')
                ->get();

            $krsValidated = [];

            $krsByNIM = collect($krsSiade)->groupBy('nim');

            foreach ($krsByNIM as $nim => $items) {
                $kodeMkKrs = collect($items)->pluck('KodeMK')->unique()->toArray();

                foreach ($mkNeofeeder ?? [] as $mkNeo) {
                    $kodeNeo = $mkNeo['kode_mata_kuliah'];
                    $namaNeo = $mkNeo['nama_mata_kuliah'] ?? '';

                    $krsValidated[] = [
                        'nim' => $nim,
                        'nama' => $items[0]->nama_lengkap,
                        'TahunAngkatan' => $items[0]->TahunAngkatan,
                        'ProdiID' => $items[0]->ProdiID,
                        'TahunID' => $items[0]->TahunID,
                        'KodeMK' => $kodeNeo,
                        'NamaMK' => $namaNeo,
                        'Semester' => $request->tahun_akademik,
                        'NamaKelas' => $kelas,
                        'NamaProdi' => '',
                        'Keterangan' => in_array($kodeNeo, $kodeMkKrs) ? 'Dari SIADE' : 'Dari Akademik',
                    ];
                }
            }
            return DataTables::of($krsValidated)
                ->addIndexColumn()
                ->make(true);
        }

        return view('neo.ExportKrs', $data);
    }

    public function getKelasPerkuliahan(Request $request)
    {
        if ($request->filled('program_studi') && $request->filled('tahun_akademik')) {

            $prodiJson = $this->neofeeder->getProdi();
            $prodiArray = $prodiJson->getData(true);
            if ($prodiArray['error_code'] !== 0) {
                return $prodiJson;
            }

            $collectProgramStudi = collect($prodiArray['data'] ?? []);
            $foundProgramStudi = $collectProgramStudi->firstWhere('kode_program_studi', $request->program_studi);
            $idProgramStudi = $foundProgramStudi['id_program_studi'] ?? null;

            $kelasJson = $this->kelas_perkuliahan($idProgramStudi, $request->tahun_akademik);
            $kelasArray = $kelasJson->getData(true);
            if ($kelasArray['error_code'] !== 0) {
                return $kelasJson;
            }

            $kelasData = collect($kelasArray['data'] ?? []);
            $uniqueKelas = $kelasData->pluck('nama_kelas_kuliah')->unique()->sort()->values();


            return response()->json([
                "error_code" => 0,
                "error_desc" => "",
                "jumlah" => $kelasData->count(),
                "data" => $uniqueKelas,
            ], 200);
        }
    }
    public function getMahasiswa(Request $request)
    {
        if ($request->ajax()) {
            $search = $request->search;

            if (empty($search)) {
                return DataTables::of([])->addIndexColumn()->make(true);
            }

            $data = [
                "act" => "GetListMahasiswa",
                "filter" => "nim like '%$search%' or nama_mahasiswa like '%$search%'",
                "order" => "nim",
                "limit" => 10,
                "offset" => 0
            ];

            $response = $this->neofeeder->getData($data);
            $responseArray = $response->getData(true);

            return DataTables::of($responseArray['data'])
                ->addIndexColumn()
                ->make(true);
        }

        return view('neo.GetMahasiswa');
    }
}
