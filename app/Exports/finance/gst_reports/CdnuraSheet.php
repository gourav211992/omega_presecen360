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


class CdnuraSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
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
        $noteCount = 0;
        $totalNoteValue = 0;
        $totalTaxable = 0;
        $totalCess = 0;

        foreach ($this->data as $item) {
            $urType = is_array($item) ? ($item['ur_type'] ?? '') : ($item->ur_type ?? '');
            $origNo = is_array($item) ? ($item['note_number'] ?? '') : ($item->note_number ?? '');
            $origDateRaw = is_array($item) ? ($item['note_date'] ?? null) : ($item->note_date ?? null);
            $origDate = $origDateRaw ? GeneralHelper::dateFormat3($origDateRaw) : '';
            $revNo = is_array($item) ? ($item['revised_note_no'] ?? '') : ($item->revised_note_no ?? '');
            $revDateRaw = is_array($item) ? ($item['revised_note_date'] ?? null) : ($item->revised_note_date ?? null);
            $revDate = $revDateRaw ? GeneralHelper::dateFormat3($revDateRaw) : '';
            $noteType = is_array($item) ? ($item['note_type'] ?? '') : ($item->note_type ?? '');
            $pos = is_array($item) ? ($item['pos'] ?? '') : ($item->pos ?? '');
            $placeOfSupply = is_array($item) ? ($item['place_of_supply'] ?? '') : ($item->place_of_supply ?? '');
            $noteValue = is_array($item) ? ($item['note_value'] ?? 0) : ($item->note_value ?? 0);
            $applicableTaxRate = is_array($item) ? ($item['applicable_tax_rate'] ?? 0) : ($item->applicable_tax_rate ?? 0);
            $rate = is_array($item) ? ($item['rate'] ?? 0) : ($item->rate ?? 0);
            $taxable = is_array($item) ? ($item['taxable_amt'] ?? 0) : ($item->taxable_amt ?? 0);
            $cess = is_array($item) ? ($item['cess'] ?? 0) : ($item->cess ?? 0);

            $noteCount += 1;
            $totalNoteValue += $noteValue;
            $totalTaxable += $taxable;
            $totalCess += $cess;

            $dataRows[] = [
                $urType,
                $origNo,
                $origDate,
                $revNo,
                $revDate,
                $noteType,
                $pos && $placeOfSupply ? ($pos.'-'.$placeOfSupply) : ($placeOfSupply ?: ''),
                $noteValue,
                $applicableTaxRate,
                $rate ? ($rate.'%') : 0,
                $taxable,
                $cess,
            ];
        }

        $rows[] = ['Summary For CDNURA','Original details', '', 'Revised details', '', '', '', '', '', '', '', 'Help'];
        $rows[] = ['', 'No. of Notes/Vouchers','','','','','', 'Total Noted Value','','','Total Taxable Value', 'Total Cess'];
        $rows[] = ['', $noteCount, '', '', '', '', '', $totalNoteValue, '', '', $totalTaxable, $totalCess];
        $rows[] = [
            'UR Type',
            'Original Note Number',
            'Original Note Date',
            'Revised Note Number',
            'Revised Note Date',
            'Note Type',
            'Place Of Supply',
            'Note Value',
            'Applicable % of Tax Rate',
            'Rate',
            'Taxable Value',
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
        return 'CDNURA';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 12 columns: A..L
                $sheet->mergeCells('B1:C1');
                $sheet->mergeCells('D1:K1');

                $sheet->getCell('L1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
                $sheet->getStyle('L1')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0000FF'],
                        'underline' => 'single',
                  
                    ],
                ]);
                $sheet->getStyle('A1:A1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('B1:C1')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('D1:L1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Sub Header Row (Row 2)
                $sheet->getStyle('A2:L2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']], // Light blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Column Header Row (Row 4)
                $sheet->getStyle('A4:C4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']], // Orange
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('D4:L4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']], // Orange
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Borders for Header Area
                $sheet->getStyle('A1:L4')->applyFromArray([
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
                $sheet->getStyle('A1:O4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:O4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
    
}
