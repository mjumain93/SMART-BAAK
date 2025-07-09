<?php

namespace App\Http\Controllers\Dosen;

use App\Exports\NilaiExport;
use App\Exports\NilaiTemplate;
use App\Http\Controllers\Controller;
use App\Services\KrsService;
use App\Services\NeofeederService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use phpDocumentor\Reflection\Types\Null_;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
    private function getNilaiHuruf($nilai_akhir, $daftarNilai)
    {
        foreach ($daftarNilai as $n) {
            if ($nilai_akhir >= $n->nilai_mulai && $nilai_akhir <= $n->nilai_sampai) {
                return $n->nama ?? 'E';
            }
        }
        return 'E';
    }

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

            $response = Http::withToken(session()->get('sso_token'))->get('https://sso.umjambi.ac.id/me');


            if ($response->ok()) {
                $DosenID = $response['user']['id'] ?? Null;
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
                // ->where('DosenID', $DosenID)
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
                    $btn .= '<a target="_blank" href="' . route('dosen.input-nilai.show', Crypt::encrypt($row->id)) . '">' . $row->NamaDosen . '</a>';
                    return $btn;
                })
                ->rawColumns(['jadwal', 'JumlahMahasiswa1', 'NamaDosen'])
                ->make(true);
        };
        return view('input-nilai.view', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);

        $path = $request->file('excel_file')->getRealPath();
        $data = Excel::toArray([], $path);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $id = Crypt::decrypt($request->input_nilai);
        } catch (\Throwable $th) {
            return redirect()->route('dosen.input-nilai.index');
        }

        $jadwal = DB::connection('mariadb')
            ->table('jadwal_kuliah')
            ->where('id', $id)
            ->first();

        if (!$jadwal) {
            return redirect()->route('input-nilai.index')->with('error', 'Jadwal tidak ditemukan.');
        }

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

            // Jika ada pencarian dari datatables
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $data_nilai->where('b.nim', 'like', "%{$search}%");
            }

            return DataTables::of($data_nilai)
                ->addIndexColumn()
                ->make(true);
        }

        return view('input-nilai.input', ['jadwal' => $jadwal]);
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
    public function update(Request $request, $id)
    {
        if (!$request->ajax()) {
            abort(403, 'Unauthorized action.');
        }

        $idJadwal = Crypt::decrypt($id);

        $jadwal = DB::connection('mariadb')
            ->table('jadwal_kuliah')
            ->where('id', $idJadwal)
            ->first();

        if (!$jadwal) {
            return response()->json([
                'error_code' => 1,
                'error_desc' => 'Jadwal tidak ditemukan.',
                'data'       => null
            ], 404);
        }

        $cekInputNilai = $this->krsService->jadwalInputNilai($jadwal->TahunID);

        if (empty($cekInputNilai)) {
            return response()->json([
                'error_code' => 1,
                'error_desc' => 'Jadwal input nilai belum dibuka.',
                'data'       => null
            ], 422);
        }

        $cekNilai = DB::connection('mariadb')
            ->table('nilai')
            ->where('NA', 'A')
            ->where('ProdiID', $jadwal->ProdiID)
            ->where('nilai_mulai', '<=', $request->nilai_angka)
            ->where('nilai_sampai', '>=', $request->nilai_angka)
            ->orderByDesc('bobot')
            ->first();

        if (!$cekNilai) {
            return response()->json([
                'error_code' => 1,
                'error_desc' => 'Rentang nilai tidak ditemukan.',
                'data'       => null
            ], 422);
        }

        $updated = DB::connection('mariadb')
            ->table('mhsw_krs')
            ->where([
                ['nim', '=', $request->nim],
                ['id_jadwal', '=', $idJadwal],
            ])
            ->update([
                'nilai_angka'           => $request->nilai_angka,
                'nilai_huruf'           => $cekNilai->nama,
                'nilai_bobot'           => $cekNilai->bobot,
                'lulus'                 => $cekNilai->lulus,
                'id_update_nilai'       => $jadwal->DosenID,
                'datetime_update_nilai' => now(),
            ]);

        if (!$updated) {
            return response()->json([
                'error_code' => 1,
                'error_desc' => 'Nilai gagal disimpan.',
                'data'       => null
            ], 422);
        }

        return response()->json([
            'error_code' => 0,
            'error_desc' => '',
            'message'    => 'Nilai berhasil disimpan.',
            'data'       => null
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function preview(Request $request)
    {
        try {
            $id = Crypt::decrypt($request->route('id'));
        } catch (\Throwable $th) {
            return redirect()->route('input-nilai.index');
        }
        $request->validate([
            'file' => 'required|file',
        ]);

        try {
            $jadwal = DB::connection('mariadb')
                ->table('jadwal_kuliah')
                ->where('id', $id)
                ->first();

            $dataNilai = DB::connection('mariadb')
                ->table('nilai')
                ->where('NA', 'A')
                ->where('ProdiID', $jadwal->ProdiID)
                ->orderByDesc('bobot')
                ->get()
                ->toArray();

            $file = $request->file('file');
            $mime = $file->getMimeType();

            if (str_contains($mime, 'spreadsheetml.sheet')) {
                $reader = IOFactory::createReader('Xlsx');
            } elseif (str_contains($mime, 'ms-excel')) {
                $reader = IOFactory::createReader('Xls');
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Format file tidak dikenali sebagai Excel.'
                ], 422);
            }

            $spreadsheet = $reader->load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet(0);
            $rows = $sheet->toArray();
            $headers = ['nim', 'nama_mahasiswa', 'nilai_sikap', 'nilai_quiz', 'nilai_uts', 'nilai_uas', 'nilai_umum', 'nilai_khusus', 'nilai_angka', 'nilai_huruf'];
            $dataRows = array_slice($rows, 11);
            $data = [];
            foreach ($dataRows as $row) {
                $values = array_slice($row, 1, 10);
                if (empty(array_filter($values))) {
                    break;
                }
                if (empty($values[0])) {
                    continue;
                }
                $combined = array_combine($headers, $values);

                $nilai_akhir =
                    ($combined['nilai_sikap']   * 0.15) +
                    ($combined['nilai_quiz']    * 0.10) +
                    ($combined['nilai_uts']     * 0.10) +
                    ($combined['nilai_uas']     * 0.20) +
                    ($combined['nilai_umum']    * 0.20) +
                    ($combined['nilai_khusus']  * 0.25);

                $combined['nilai_angka'] = round($nilai_akhir, 2);
                $combined['nilai_huruf'] = $this->getNilaiHuruf($nilai_akhir, $dataNilai);

                $data[] = $combined;
            }

            $html = '';
            $html .= '<table class="table table-striped table-bordered">
            <thead>
            <tr>
    <th rowspan="4" class="text-center align-middle">No.</th>
    <th rowspan="4" class="text-center align-middle">Nama Mahasiswa</th>
    <th rowspan="4" class="text-center align-middle">NPM</th>
    <th colspan="6" class="text-center align-middle">Penilaian Menurut Presentase</th>
    <th rowspan="2" colspan="2" class="text-center align-middle">Keterangan Nilai Akhir</th>
</tr>
<tr>
    <th rowspan="2" class="text-center align-middle">Sikap</th>
    <th colspan="3" class="text-center align-middle">Pengetahuan</th>
    <th colspan="2" class="text-center align-middle">Keterampilan</th>
</tr>
<tr>
    <th class="text-center align-middle">Quiz</th>
    <th class="text-center align-middle">UTS</th>
    <th class="text-center align-middle">UAS</th>
    <th class="text-center align-middle">Umum</th>
    <th class="text-center align-middle">Khusus</th>
    <th rowspan="2" class="text-center align-middle">Angka</th>
    <th rowspan="2" class="text-center align-middle">Huruf</th>
</tr>
<tr>
    <th class="text-center align-middle">15%</th>
    <th class="text-center align-middle">10%</th>
    <th class="text-center align-middle">10%</th>
    <th class="text-center align-middle">20%</th>
    <th class="text-center align-middle">20%</th>
    <th class="text-center align-middle">25%</th>
</tr>
            </thead>
            <tbody>';
            foreach ($data as $key => $value) {
                $html .= '<tr>';
                $html .= '<td>' . ($key + 1) . '</td>';
                $html .= '<td>' . $value['nim'] . '</td>';
                $html .= '<td>' . $value['nama_mahasiswa'] . '</td>';
                $html .= '<td class="text-center">' . $value['nilai_sikap'] . '</td>';
                $html .= '<td class="text-center">' . $value['nilai_quiz'] . '</td>';
                $html .= '<td class="text-center">' . $value['nilai_uts'] . '</td>';
                $html .= '<td class="text-center">' . $value['nilai_uas'] . '</td>';
                $html .= '<td class="text-center">' . $value['nilai_umum'] . '</td>';
                $html .= '<td class="text-center">' . $value['nilai_khusus'] . '</td>';
                $html .= '<td class="text-center">' . $value['nilai_angka'] . '</td>';
                $html .= '<td class="text-center">' . $value['nilai_huruf'] . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';

            return response()->json([
                'success' => true,
                'data' => $html
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function downloadTemplate(Request $request)
    {
        try {
            $id = Crypt::decrypt($request->id);
        } catch (\Throwable $th) {
            return redirect()->route('input-nilai.index');
        }
        $data = DB::connection('mariadb')
            ->table('mhsw_krs as a')
            ->join('mhsw as b', 'a.nim', '=', 'b.nim')
            ->select([
                'b.nama_lengkap as nama_lengkap',
                'a.status_pa',
                'b.nim',
                'a.nilai_angka',
                'a.nilai_huruf',
                'a.lulus',
                'a.id_jadwal',
                'b.ProdiID',
            ])
            ->where('a.id_jadwal', $id)
            ->where('a.NA', 'A')
            ->orderBy('b.nama_lengkap')
            ->get();

        $startRow = 12;
        return Excel::download(new NilaiTemplate($data, $startRow), 'template_upload_nilai' . time() . '.xlsx');
    }
    public function export(Request $request)
    {
        try {
            $id = Crypt::decrypt($request->id);
        } catch (\Throwable $th) {
            return redirect()->route('input-nilai.index');
        }
        $data = DB::connection('mariadb')
            ->table('mhsw_krs as a')
            ->join('mhsw as b', 'a.nim', '=', 'b.nim')
            ->select([
                'b.nama_lengkap as nama_lengkap',
                'a.status_pa',
                'b.nim',
                'a.nilai_angka',
                'a.nilai_huruf',
                'a.lulus',
                'a.id_jadwal',
                'b.ProdiID',
            ])
            ->where('a.id_jadwal', $id)
            ->where('a.NA', 'A')
            ->orderBy('b.nama_lengkap')
            ->get();

        $startRow = 12;
        return Excel::download(new NilaiExport($data, $startRow), 'data_nilai' . time() . '.xlsx');
    }
}
