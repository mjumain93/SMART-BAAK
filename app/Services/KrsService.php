<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class KrsService
{
    public function getKrs(array $filters = [])
    {
        $query = DB::connection('mariadb')
            ->table('mhsw_krs as a')
            ->select([
                'a.id as id',
                'b.id as JadwalID',
                'a.status_pa',
                'a.nim as nim',
                'h.nama_lengkap as nama',
                'h.foto as FotoMhsw',
                'b.ProdiID',
                'a.TahunID',
                'b.KelasID',
                'c.nama as NamaKelas',
                'b.HariID',
                'd.nama as HariNama',
                'b.RuangID',
                'e.nama as NamaRuang',
                'b.DosenID',
                'f.nama_lengkap as NamaDosen',
                'b.Kelompok',
                'b.MkID',
                'g.sesi as Semester',
                'g.MkKode',
                'g.NamaID',
                'g.NamaEn',
                'g.MkJenis',
                'g.MkTipe',
                'g.sks_tatap_muka',
                'g.sks_praktek',
                'g.sks_praktek_lap',
                'g.sks_simulasi',
                'g.sks',
                'b.jam_mulai',
                'b.jam_selesai',
                'a.nilai_angka',
                'a.nilai_huruf',
                'a.nilai_bobot',
                DB::raw('a.nilai_bobot * g.sks as nilai_mutu'),
                'a.lulus',
                'a.status_input_kusioner',
                'a.datetime_input_kusioner',
                'a.id_jenis_kkn',
                'a.datetime_daftar_kkn',
                'a.kelompok_kkn',
                'a.judul_penelitian',
                'a.id_proposal',
                'a.datetime_daftar_proposal',
                'a.status_proposal',
                'a.nilai_proposal',
                'a.id_ta',
                'a.datetime_daftar_ta',
                'a.status_ta',
                'a.nilai_ta',
            ])
            ->join('jadwal as b', 'a.id_jadwal', '=', 'b.id')
            ->join('kelas as c', 'b.KelasID', '=', 'c.id')
            ->join('hari_kerja as d', 'b.HariID', '=', 'd.id')
            ->join('ruang as e', 'b.RuangID', '=', 'e.id')
            ->join('pegawai as f', 'b.DosenID', '=', 'f.id')
            ->join('mk as g', 'b.MkID', '=', 'g.id')
            ->join('mhsw as h', 'a.nim', '=', 'h.nim')
            ->where('a.NA', 'A')
            ->where('b.NA', 'A');
        if ($filters) {
            foreach ($filters as $key => $value) {
                $query->where("$key", $value);
            }
        }

        return $query->orderBy('a.nim')->get();
    }
    public function getTahuAkademik()
    {
        $tahunMulai = 2020;
        $tahunAkhir = Carbon::now()->format('Y');

        $tahun_akademik = [];

        for ($i = $tahunMulai; $i <= $tahunAkhir; $i++) {
            $tahun_akademik[] = $i . '1'; // Semester Ganjil
            $tahun_akademik[] = $i . '2'; // Semester Genap
        }
        return $tahun_akademik;
    }
    public function jadwalInputNilai($tahunID)
    {
        $today = Carbon::today()->format('Y-m-d');
        $cek_kalender = DB::connection('mariadb')
            ->table('kalender_detail')
            ->where('ts', $tahunID)
            ->where('com_nilai', 'IYA')
            ->where('NA', 'A')
            ->whereDate('mulai', '<=', $today)
            ->whereDate('selesai', '>=', $today)
            ->first();

        return $cek_kalender ?: null;
    }
}
