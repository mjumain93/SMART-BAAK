<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Files\LocalTemporaryFile;

class NilaiExport implements WithEvents
{
    protected $data;
    protected $startRow;

    public function __construct($data, $startRow = 15)
    {
        $this->data = $data;
        $this->startRow = $startRow;
    }

    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event) {
                $templatePath = storage_path('app/templates/template_upload_nilai.xlsx');
                $tempFile = new LocalTemporaryFile($templatePath);

                $event->writer->reopen($tempFile, Excel::XLSX);

                $spreadsheet = $event->writer->getDelegate();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(12);

                $persenSikap = $sheet->getCell('D11')->getValue();
                $persenQuiz = $sheet->getCell('E11')->getValue();
                $persenUts = $sheet->getCell('F11')->getValue();
                $persenUas = $sheet->getCell('G11')->getValue();
                $persenUmum = $sheet->getCell('H11')->getValue();
                $persenKhusus = $sheet->getCell('I11')->getValue();

                $rowNumber = $this->startRow;
                $no = 1;

                foreach ($this->data as $item) {
                    $sheet->setCellValue("A{$rowNumber}", $no++);
                    $sheet->setCellValue("B{$rowNumber}", $item->nama_lengkap);
                    $sheet->setCellValue("C{$rowNumber}", $item->nim);
                    $sheet->getStyle("C{$rowNumber}")->getNumberFormat()->setFormatCode('0');
                    $sheet->setCellValue("D{$rowNumber}", ($item->nilai_angka / $persenSikap));
                    $sheet->setCellValue("E{$rowNumber}", ($item->nilai_angka / $persenQuiz));
                    $sheet->setCellValue("F{$rowNumber}", ($item->nilai_angka / $persenUts));
                    $sheet->setCellValue("G{$rowNumber}", ($item->nilai_angka / $persenUas));
                    $sheet->setCellValue("H{$rowNumber}", ($item->nilai_angka / $persenUmum));
                    $sheet->setCellValue("I{$rowNumber}", ($item->nilai_angka / $persenKhusus));
                    $sheet->setCellValue("J{$rowNumber}", $item->nilai_angka);
                    $sheet->setCellValue("K{$rowNumber}", $item->nilai_huruf);
                    $rowNumber++;
                }
            },
        ];
    }
}
