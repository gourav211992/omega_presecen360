<?php

namespace App\Exports\finance\gst_reports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Hsnb2bSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [];
        $dataRows = [];

        $totalQuantity = 0;
        $totalValue = 0;
        $totalTaxable = 0;
        $totalIgst = 0;
        $totalCgst = 0;
        $totalSgst = 0;
        $totalCess = 0;
        $totalHsn = 0;

        foreach ($this->data as $item) {
            $hsn = is_array($item) ? ($item['hsn_code'] ?? '') : ($item->hsn_code ?? '');
            $description = is_array($item) ? ($item['description'] ?? '') : ($item->description ?? '');
            $uqc = is_array($item) ? ($item['uqc'] ?? '') : ($item->uqc ?? '');
            $qty = is_array($item) ? ($item['qty'] ?? 0) : ($item->qty ?? 0);
            $rate = is_array($item) ? ($item['rate'] ?? 0) : ($item->rate ?? 0);
            $taxableAmt = is_array($item) ? ($item['taxable_amt'] ?? 0) : ($item->taxable_amt ?? 0);
            $igst = is_array($item) ? ($item['igst'] ?? 0) : ($item->igst ?? 0);
            $cgst = is_array($item) ? ($item['cgst'] ?? 0) : ($item->cgst ?? 0);
            $sgst = is_array($item) ? ($item['sgst'] ?? 0) : ($item->sgst ?? 0);
            $cess = is_array($item) ? ($item['cess'] ?? 0) : ($item->cess ?? 0);

            $rowTotalValue = ($taxableAmt + $igst + $cgst + $sgst + $cess);

            $totalQuantity += ($qty ?: 0);
            $totalValue += $rowTotalValue;
            $totalTaxable += $taxableAmt;
            $totalIgst += $igst;
            $totalCgst += $cgst;
            $totalSgst += $sgst;
            $totalCess += $cess;
            $totalHsn += !empty($hsn)? 1 : 0;

            $dataRows[] = [
                $hsn,
                $description,
                $uqc,
                $qty,
                $rowTotalValue,
                $rate ? ($rate.'%') : 0,
                $taxableAmt,
                $igst,
                $cgst,
                $sgst,
                $cess,
            ];
        }

        $rows[] = ['Summary For HSN (B2B)', '', '', '', '', '', '', '', '', '', 'Help'];
        $rows[] = ['No. of HSN', '','','','Total Value', '', 'Total Taxable Value', 'Total Integrated Tax', 'Total Central Tax', 'Total State/UT Tax', 'Total Cess'];
        $rows[] = [$totalHsn,'','','', $totalValue, '', $totalTaxable, $totalIgst, $totalCgst, $totalSgst, $totalCess];
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
            'Cess Amount',
        ];

        return array_merge($rows, $dataRows);
    }

    public function title(): string
    {
        return 'HSNB2B';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                 $sheet->mergeCells('A1:J1');

                 $sheet->getCell('K1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                 // Optional styling for the link
                 $sheet->getStyle('K1')->applyFromArray([
                     'font' => [
                         'color' => ['rgb' => '0000FF'],
                         'underline' => 'single',
                   
                     ],
                 ]);
                // $sheet->mergeCells('B2:C2');
                // $sheet->mergeCells('D2:E2');
                $sheet->getStyle('A1:K1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A2:K2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ],
                    ],
                ]);
                $sheet->getStyle('A4:K4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A1:K4')->applyFromArray([
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
