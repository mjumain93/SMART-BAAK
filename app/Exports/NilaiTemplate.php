<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Files\LocalTemporaryFile;

class NilaiTemplate implements WithEvents
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

                $rowNumber = $this->startRow;
                $no = 1;

                foreach ($this->data as $item) {
                    $sheet->setCellValue("A{$rowNumber}", $no++);
                    $sheet->setCellValue("B{$rowNumber}", $item->nama_lengkap);
                    $sheet->setCellValue("C{$rowNumber}", $item->nim);
                    $sheet->getStyle("C{$rowNumber}")->getNumberFormat()->setFormatCode('0');
                    $sheet->setCellValue("D{$rowNumber}", '');
                    $sheet->setCellValue("E{$rowNumber}", '');
                    $sheet->setCellValue("F{$rowNumber}", '');
                    $sheet->setCellValue("G{$rowNumber}", '');
                    $sheet->setCellValue("H{$rowNumber}", '');
                    $sheet->setCellValue("I{$rowNumber}", '');
                    $sheet->setCellValue("J{$rowNumber}", "=D{$rowNumber}*\$D\$11+E{$rowNumber}*\$E\$11+F{$rowNumber}*\$F\$11+G{$rowNumber}*\$G\$11+H{$rowNumber}*\$H\$11+I{$rowNumber}*\$I\$11");
                    $sheet->setCellValue("K{$rowNumber}", '');
                    $rowNumber++;
                }
            },
        ];
    }
}
