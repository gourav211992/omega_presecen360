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

class CdnurSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
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
        $totalTaxableValue = 0;
        $totalCess = 0;

        foreach ($this->data as $item) {
            $urType = is_array($item) ? ($item['ur_type'] ?? '') : ($item->ur_type ?? '');
            $noteNumber = is_array($item) ? ($item['note_number'] ?? '') : ($item->note_number ?? '');
            $noteDateRaw = is_array($item) ? ($item['note_date'] ?? null) : ($item->note_date ?? null);
            $noteDate = $noteDateRaw ? GeneralHelper::dateFormat3($noteDateRaw) : '';
            $noteType = is_array($item) ? ($item['note_type'] ?? '') : ($item->note_type ?? '');
            $pos = is_array($item) ? ($item['pos'] ?? '') : ($item->pos ?? '');
            $placeOfSupply = is_array($item) ? ($item['place_of_supply'] ?? '') : ($item->place_of_supply ?? '');
            $noteValue = is_array($item) ? ($item['note_value'] ?? 0) : ($item->note_value ?? 0);
            $applicableTaxRate = is_array($item) ? ($item['applicable_tax_rate'] ?? 0) : ($item->applicable_tax_rate ?? 0);
            $rate = is_array($item) ? ($item['rate'] ?? 0) : ($item->rate ?? 0);
            $taxableAmt = is_array($item) ? ($item['taxable_amt'] ?? 0) : ($item->taxable_amt ?? 0);
            $cess = is_array($item) ? ($item['cess'] ?? 0) : ($item->cess ?? 0);

            $noteCount += 1;
            $totalNoteValue += $noteValue;
            $totalTaxableValue += $taxableAmt;
            $totalCess += $cess;

            $dataRows[] = [
                $urType,
                $noteNumber,
                $noteDate,
                $noteType,
                $pos && $placeOfSupply ? ($pos.'-'.$placeOfSupply) : ($placeOfSupply ?: ''),
                $noteValue,
                $applicableTaxRate,
                $rate ? ($rate.'%') : 0,
                $taxableAmt,
                $cess,
            ];
        }

        $rows[] = ['Summary for CDNUR(9B)', '', '', '', '', '', '', '', '', 'Help'];
        $rows[] = ['', 'No. of Notes/Vouchers', '','','', 'Total Note Value', '','', 'Total Taxable Value', 'Total Cess'];
        $rows[] = ['', $noteCount, '','','', $totalNoteValue, '','', $totalTaxableValue, $totalCess];
        $rows[] = [
            'UR Type', 
            'Note Number', 
            'Note Date', 
            'Note Type', 
            'Place Of Supply', 
            'Note Value', 
            'Applicable % of Tax Rate', 
            'Rate',
            'Taxable Value', 
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
        return 'CDNUR';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge Header Cells (10 cols)
                $sheet->mergeCells('A1:I1');
                
                $sheet->getCell('J1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
                $sheet->getStyle('J1')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0000FF'],
                        'underline' => 'single',
                  
                    ],
                ]);


                // Styles
                $sheet->getStyle('A1:J1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:J2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A4:J4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Add Borders
                $sheet->getStyle('A1:J4')->applyFromArray([
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
