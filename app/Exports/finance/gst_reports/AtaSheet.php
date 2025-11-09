<?php

namespace App\Exports\finance\gst_reports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use App\Helpers\GeneralHelper;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AtaSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
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
        $totalAdvance = 0;
        $totalCess = 0;

        foreach ($this->data as $item) {
            $year = is_array($item) ? ($item['year'] ?? '') : ($item->year ?? '');
            $month = is_array($item) ? ($item['month'] ?? '') : ($item->month ?? '');
            $monthLabel = $month !== '' ? \DateTime::createFromFormat('!m', $month)->format('F') : '';
            $pos = is_array($item) ? ($item['pos'] ?? '') : ($item->pos ?? '');
            $placeOfSupply = is_array($item) ? ($item['place_of_supply'] ?? '') : ($item->place_of_supply ?? '');
            $applicableTaxRate = is_array($item) ? ($item['applicable_tax_rate'] ?? 0) : ($item->applicable_tax_rate ?? 0);
            $rate = is_array($item) ? ($item['rate'] ?? 0) : ($item->rate ?? 0);
            $advance = is_array($item) ? ($item['taxable_amt'] ?? 0) : ($item->taxable_amt ?? 0);
            $cess = is_array($item) ? ($item['cess'] ?? 0) : ($item->cess ?? 0);

            $totalAdvance += $advance;
            $totalCess += $cess;

            $dataRows[] = [
                $year,
                $monthLabel,
                $pos && $placeOfSupply ? ($pos.'-'.$placeOfSupply) : ($placeOfSupply ?: ''),
                $applicableTaxRate,
                $rate ? ($rate.'%') : 0,
                $advance,
                $cess,
            ];
        }

        $rows[] = ['Summary For Amended Tax Liability(Advance Received)', 'Original details', '', 'Revised details', '', '', 'Help'];
        $rows[] = ['', '', '', '', '', 'Total Advance Received', 'Total Cess'];
        $rows[] = ['', '', '', '', '', $totalAdvance, $totalCess];
        $rows[] = [
            'Financial Year',
            'Original Month',
            'Original Place Of Supply',
            'Applicable % of Tax Rate',
            'Rate',
            'Gross Advance Received',
            'Cess Amount',
        ];

        return array_merge($rows, $dataRows);
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'ATA';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 7 columns: A..G
                $sheet->mergeCells('B1:C1');
                $sheet->mergeCells('D1:F1');

                // $sheet->mergeCells('A2:E2');
                // $sheet->mergeCells('F2:F2'); // Total Advance Received
                // $sheet->mergeCells('G2:G2'); // Total Cess

                // ✅ Main Header Row (Row 1)
                $sheet->getStyle('A1:A1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('B1:C1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '121111']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('D1:G1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Sub Header Row (Row 2)
                $sheet->getStyle('A2:G2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']], // Light blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('A4:C4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']], // Orange
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Column Header Row (Row 4)
                $sheet->getStyle('D4:G4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']], // Orange
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Borders for Header Area
                $sheet->getStyle('A1:G4')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getCell('G1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
                $sheet->getStyle('G1')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0000FF'],
                        'underline' => 'single',
                  
                    ],
                ]);

                // ✅ Adjust Row Heights
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(4)->setRowHeight(20);

                // ✅ Center Align All Headers
                $sheet->getStyle('A1:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:G4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
