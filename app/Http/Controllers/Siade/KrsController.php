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
}
