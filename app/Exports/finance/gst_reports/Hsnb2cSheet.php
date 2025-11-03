<?php

namespace App\Exports\finance\gst_reports;

use Maatwebsite\Excel\Concerns\FromArray;
use App\Helpers\GeneralHelper;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Hsnb2cSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
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
        $totalValues = 0;
        $totalTaxableValue = 0;
        $totalCess = 0;
        $totalIgst = 0;
        $totalSgst = 0;
        $tatalCgst = 0;

        foreach ($this->data as $item) {
            // Support both array and object structures
            $hsn = is_array($item) ? ($item['hsn_code'] ?? '') : ($item->hsn_code ?? '');
            $description = is_array($item) ? ($item['description'] ?? '') : ($item->description ?? '');
            $uqc = is_array($item) ? ($item['uqc'] ?? '') : ($item->uqc ?? '');
            $qty = is_array($item) ? ($item['qty'] ?? 0) : ($item->qty ?? 0);
            $taxableAmt = is_array($item) ? ($item['taxable_amt'] ?? 0) : ($item->taxable_amt ?? 0);
            $cess = is_array($item) ? ($item['cess'] ?? 0) : ($item->cess ?? 0);
            $igst = is_array($item) ? ($item['igst'] ?? 0) : ($item->igst ?? 0);
            $cgst = is_array($item) ? ($item['cgst'] ?? 0) : ($item->cgst ?? 0);
            $sgst = is_array($item) ? ($item['sgst'] ?? 0) : ($item->sgst ?? 0);
            $totalValue = $taxableAmt + $igst + $sgst + $cgst;
            $rate = is_array($item) ? ($item['rate'] ?? 0) : ($item->rate  ?? 0);

            $totalValues += $totalValue;
            $totalTaxableValue += $taxableAmt;
            $totalCess += $cess;
            $totalIgst += $igst;
            $totalSgst += $sgst;
            $tatalCgst += $cgst;

            if(!empty($hsn)){
               $hsnCount++ ;
            }

            $dataRows[] = [
                $hsn,
                $description,
                $uqc,
                $qty,
                $totalValue,
                $rate,
                $taxableAmt,
                $igst,
                $cgst,
                $sgst,
                $cess
            ];
        }
        
            // Row 1: Main Title
            $rows[] = ['Summary For HSN(12)', '','','','', '', '', '', '','','Help'];

            // Row 2: Sub Header Titles
            $rows[] = ['No. of HSN', '','','', 'Total Value','', 'Total Taxable Value','Total Integrated Tax', 'Total Central Tax', 'Total State/UT Tax','Total Cess'];

            // Row 3: Sub Header Values
            $rows[] = [$hsnCount,'','','', $totalValues, '', $totalTaxableValue, $totalIgst, $totalCess, $totalSgst, $totalCess];

            // Row 4: Column Headings
            $rows[] = [
                'HSN',
                'Description',
                'UQC',
                'Total Quantity',
                'Total Value',
                'Rate',
                'Taxable Value',
                'Integrated Tax Amount',
                'Central Tax Amount',
                'State/UT Tax Amount',
                'Cess Amount'
            ];

            return array_merge($rows, $dataRows);
        
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'HSNB2C';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:J1'); // Summary

                $sheet->getCell('K1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
                $sheet->getStyle('K1')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0000FF'],
                        'underline' => 'single',
                  
                    ],
                ]);

                $sheet->getStyle('A1:K1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('A2:K2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']], // Light blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Column Header Row (Row 4)
                $sheet->getStyle('A4:K4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']], // Orange
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);
                
                // ✅ Borders for Header Area
                $sheet->getStyle('A1:K4')->applyFromArray([
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
                $sheet->getStyle('A1:K4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:K4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
