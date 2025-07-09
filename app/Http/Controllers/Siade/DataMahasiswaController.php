<?php

namespace App\Http\Controllers\Siade;

use App\Http\Controllers\Controller;
use App\Services\KrsService;
use App\Services\NeofeederService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class DataMahasiswaController extends Controller
{
    protected $neofeeder;
    protected $krsService;

    public function __construct(NeofeederService $neofeeder, KrsService $krsService)
    {
        $this->neofeeder = $neofeeder;
        $this->krsService = $krsService;
    }
    public function dataMahasiswa(Request $request)
    {
        try {
            if ($request->ajax()) {
                if ($request->filled('nim')) {
                    $filters = [
                        'a.nim' => $request->nim,
                    ];

                    $groupedKrs = collect($this->krsService->getKrs($filters))
                        ->groupBy(function ($item) {
                            return "{$item->nama} ({$item->nim})";
                        })->map(function ($group) {
                            return $group->groupBy(function ($item) {
                                return "TAHUN AKADEMIK {$item->TahunID} - SEMESTER {$item->Semester}";
                            })->sortKeys();
                        });

                    $response = '';

                    foreach ($groupedKrs as $namaNim => $byTahun) {
                        // dd($groupedKrs);
                        $nama = strtoupper($namaNim);
                        $response .= "<table class='table table-striped table-bordered' style='100%' >";
                        foreach ($byTahun as $Semester => $items) {
                            // dd($items); 
                            $response .= "<thead>
                                            <th colspan='8' style='vertical-align: middle; font-size: 20px; '>{$Semester}</th>
                                            <tr>
                                                <th valign='middle' style='width:30px;' rowspan='2'>NO</th>
                                                <th valign='middle' style='width:100px' rowspan='2'>KODE MK</th>
                                                <th valign='middle' rowspan='2'>NAMA MATAKULIAH</th>
                                                <th colspan='4' class='text-center'>NILAI</th>
                                                <th valign='middle' rowspan='2' class='text-center'>DOSEN PENGAMPU</th>
                                            </tr>
                                            <tr>
                                                <th>ANGKA</th>
                                                <th>HURUF</th>
                                                <th>BOBOT</th>
                                                <th>MUTU</th>
                                            </tr>
                                        </thead>
                                        <tbody>";
                            $no = 1;
                            foreach ($items as $item) {
                                // dd($item);
                                $response .= "
                                        <tr>
                                            <td>{$no}</td>
                                            <td>{$item->MkKode}</td>
                                            <td>" . strtoupper($item->NamaID) . "</td>
                                            <td>{$item->nilai_angka}</td>
                                            <td>{$item->nilai_huruf}</td>
                                            <td>{$item->nilai_bobot}</td>
                                            <td>{$item->nilai_mutu}</td>
                                            <td>{$item->NamaDosen}</br>{$item->status_pa}</td>
                                        </tr>";
                                $no++;
                            }
                        }
                        $response .= "</tbody></table><br>";
                    }

                    return response()->json([
                        'nama' => $nama ?? null,
                        'krs' => $response
                    ]);
                }

                if ($request->filled('npm')) {
                    $mahasiswa = DB::connection('mariadb')
                        ->table('mhsw')
                        ->where('nim', $request->npm)
                        ->get();

                    return DataTables::of($mahasiswa)
                        ->addIndexColumn()
                        ->addColumn('aksi', function ($row) {
                            $btn = '<button type="button" data-nim="' . $row->nim . '" class="getKrs btn btn-primary btn-sm">KRS</button>';
                            $btn .= ' <button type="button" data-nim="' . $row->nim . '" class="getKhs btn btn-danger btn-sm">KHS</button>';
                            return $btn;
                        })
                        ->rawColumns(['aksi'])
                        ->make(true);
                }

                if (!$request->filled('program_studi') || !$request->filled('tahun_angkatan')) {
                    return DataTables::of([])->addIndexColumn()->make(true);
                }

                $data_mahasisswa = DB::connection('mariadb')
                    ->table('mhsw')
                    ->where('ProdiID', '=', $request->program_studi)
                    ->where('TahunID', '=', $request->tahun_angkatan)
                    ->get();

                // dd($data_mahasisswa);

                $prodiJson = $this->neofeeder->getProdi();
                $prodiArray = $prodiJson->getData(true);
                if ($prodiArray['error_code'] !== 0) {
                    return $prodiJson;
                }

                $prodi = $prodi = collect($prodiArray['data'])->mapWithKeys(function ($item) {
                    return [
                        $item['kode_program_studi'] => $item['nama_jenjang_pendidikan'] . ' - ' . $item['nama_program_studi']
                    ];
                });

                return DataTables::of($data_mahasisswa)
                    ->addIndexColumn()
                    ->editColumn('nama_lengkap', function ($row) {
                        return $row->nama_lengkap;
                    })
                    ->editColumn('ProdiID', function ($row) use ($prodi) {
                        return $prodi[$row->ProdiID];
                    })
                    ->editColumn('KelasID', function ($row) {
                        if ($row->KelasID === 2) {
                            return 'Reguler A';
                        } elseif ($row->KelasID === 3) {
                            return 'Reguler B (Blended Learning)';
                        } else {
                            return '-';
                        }
                    })
                    ->addColumn('aksi', function ($row) {
                        $btn = '<button type="button" data-nim="' . $row->nim . '" class="getKrs btn btn-primary btn-sm">KRS</button>';
                        $btn .= ' <button type="button" data-nim="' . $row->nim . '" class="getKhs btn btn-danger btn-sm">KHS</button>';
                        return $btn;
                    })
                    ->rawColumns(['aksi', 'nama_lengkap', 'ProdiID', 'KelasID'])
                    ->make(true);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        $prodiJson = $this->neofeeder->getProdi();
        $prodiArray = $prodiJson->getData(true);
        if ($prodiArray['error_code'] != 0) {
            return $prodiJson;
        }
        $data['prodi'] = $prodiArray['data'] ?? [];
        $data['tahun_angkatan'] = DB::connection('mariadb')
            ->table('mhsw')
            ->select('TahunID')
            ->distinct()
            ->pluck('TahunID');
        return view('siade.DataMahasiswa', $data);
    }
}
