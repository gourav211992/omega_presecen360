<?php

namespace App\Exports\finance\gst_reports;

use Maatwebsite\Excel\Concerns\FromArray;
use App\Helpers\GeneralHelper;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class NilSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
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
        $hsnCount = 0;
        $totalNil =0;
        $totalExemp = 0;
        $totalNonGst = 0;

        foreach ($this->data as $item) {
            // Support both array and object structures
            $description = is_array($item) ? ($item['description'] ?? '') : ($item->description ?? '');
            $nilAmt = is_array($item) ? ($item['nil_amt'] ?? 0) : ($item->nil_amt ?? 0);
            $exptAmt = is_array($item) ? ($item['expt_amt'] ?? 0) : ($item->expt_amt ?? 0);
            $nonGstAmt = is_array($item) ? ($item['non_gst_amt'] ?? 0) : ($item->non_gst_amt ?? 0);

           
            $totalNil += $nilAmt;
            $totalExemp += $exptAmt;
            $totalNonGst += $nonGstAmt;

            if(!empty($hsn)){
               $hsnCount++ ;
            }

            $dataRows[] = [
                $description,
                $nilAmt,
                $exptAmt,
                $nonGstAmt,
            ];
        }
        
            // Row 1: Main Title
            $rows[] = ['Summary For Nil rated, exempted and non GST outward supplies (8)', '','','Help'];

            // Row 2: Sub Header Titles
            $rows[] = ['','Total Nil Rated Supplies','Total Exempted Supplies', 'Total Non-GST Supplies'];

            // Row 3: Sub Header Values
            $rows[] = ['', $totalNil, $totalExemp, $totalNonGst];

            // Row 4: Column Headings
            $rows[] = [
                'Description',
                'Nil Rated Supplies',
                'Exempted(other than nil rated/non GST supply)',
                'Non-GST Supplies',
            ];

            return array_merge($rows, $dataRows);
        
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'EXEMP';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:C1'); // Summary

                $sheet->getCell('D1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
                $sheet->getStyle('D1')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0000FF'],
                        'underline' => 'single',
                  
                    ],
                ]);

                $sheet->getStyle('A1:D1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('A2:D2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']], // Light blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Column Header Row (Row 4)
                $sheet->getStyle('A4:D4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']], // Orange
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);
                
                // ✅ Borders for Header Area
                $sheet->getStyle('A1:D4')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // ✅ Adjust Row Heights
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(4)->setRowHeight(20);

                // ✅ Center Align All Headers
                $sheet->getStyle('A1:D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:D4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
