<?php

namespace App\Exports\finance\gst_reports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromArray;
use App\Helpers\GeneralHelper;

class DocIssueSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    protected $data;

    function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [];
        $dataRows = [];
        $totalAllNumber = 0;
        $totalCancelValue = 0;

        foreach ($this->data as $item) {
            $natureOfDocument = is_array($item) ? ($item['nature_of_document'] ?? '') : ($item->nature_of_document ?? '');
            $srNoFrom = is_array($item) ? ($item['sr_no_from'] ?? '') : ($item->sr_no_from ?? '');
            $srNoTo = is_array($item) ? ($item['sr_no_to'] ?? null) : ($item->sr_no_to ?? null);
            $totalNumber = is_array($item) ? ($item['total_number'] ?? '') : ($item->total_number ?? '');
            $cancelled = is_array($item) ? ($item['cancelled'] ?? '') : ($item->cancelled ?? '');

            $totalAllNumber += (float)$totalNumber;
            $totalCancelValue += !empty($cancelled) ? 1 : 0;

            $dataRows[] = [
               $natureOfDocument,
               $srNoFrom,
               $srNoTo,
               $totalNumber,
               $cancelled
            ];
        }

        $rows[] = ['Summary of documents issued during the tax period (13)', '', '', '', 'Help'];
        $rows[] = ['', '','', 'Total Number', 'Total Cancelled'];
        $rows[] = ['', '','', $totalAllNumber, $totalCancelValue];
        $rows[] = [
            'Nature of Document', 
            'Sr. No. From', 
            'Sr. No. To', 
            'Total Number', 
            'Cancelled'
        ];

        return array_merge($rows, $dataRows);
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'DOC';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge Header Cells (10 cols)
                $sheet->mergeCells('A1:D1');
                // $sheet->mergeCells('B2:C2');
                // $sheet->mergeCells('D2:E2');
                // $sheet->mergeCells('F2:G2');
                // $sheet->mergeCells('H2:I2');

                $sheet->getCell('E1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
                $sheet->getStyle('E1')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0000FF'],
                        'underline' => 'single',
                  
                    ],
                ]);

                // Styles
                $sheet->getStyle('A1:E1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:E2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A4:E4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Add Borders
                $sheet->getStyle('A1:E4')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ],
                    ],
                ]);
            }
        ];
    }
}
