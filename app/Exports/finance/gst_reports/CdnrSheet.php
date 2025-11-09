<?php

namespace App\Exports\finance\gst_reports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CdnrSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
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
        $uniqueRecipients = [];
        $noteCount = 0;
        $totalNoteValue = 0;
        $totalTaxableValue = 0;
        $totalCess = 0;

        foreach ($this->data as $item) {
            $partyGstin = is_array($item) ? ($item['party_gstin'] ?? '') : ($item->party_gstin ?? '');
            $partyName = is_array($item) ? ($item['party_name'] ?? '') : ($item->party_name ?? '');
            $noteNumber = is_array($item) ? ($item['note_number'] ?? '') : ($item->note_number ?? '');
            $noteDateRaw = is_array($item) ? ($item['note_date'] ?? null) : ($item->note_date ?? null);
            $noteDate = $noteDateRaw ? \App\Helpers\GeneralHelper::dateFormat3($noteDateRaw) : '';
            $noteType = is_array($item) ? ($item['note_type'] ?? '') : ($item->note_type ?? '');
            $pos = is_array($item) ? ($item['pos'] ?? '') : ($item->pos ?? '');
            $placeOfSupply = is_array($item) ? ($item['place_of_supply'] ?? '') : ($item->place_of_supply ?? '');
            $reverseCharge = is_array($item) ? ($item['reverse_charge'] ?? 0) : ($item->reverse_charge ?? 0);
            $noteSupplyType = is_array($item) ? ($item['note_type'] ?? '') : ($item->note_type ?? '');
            $noteValue = is_array($item) ? ($item['note_value'] ?? 0) : ($item->note_value ?? 0);
            $applicableTaxRate = is_array($item) ? ($item['applicable_tax_rate'] ?? 0) : ($item->applicable_tax_rate ?? 0);
            $rate = is_array($item) ? ($item['rate'] ?? 0) : ($item->rate ?? 0);
            $taxableAmt = is_array($item) ? ($item['taxable_amt'] ?? 0) : ($item->taxable_amt ?? 0);
            $cess = is_array($item) ? ($item['cess'] ?? 0) : ($item->cess ?? 0);

            if (!empty($partyGstin)) {
                $uniqueRecipients[$partyGstin] = true;
            }
            $noteCount += 1;
            $totalNoteValue += $noteValue;
            $totalTaxableValue += $taxableAmt;
            $totalCess += $cess;

            $dataRows[] = [
                $partyGstin,
                $partyName,
                $noteNumber,
                $noteDate,
                $noteType,
                $pos && $placeOfSupply ? ($pos.'-'.$placeOfSupply) : ($placeOfSupply ?: ''),
                $reverseCharge,
                $noteSupplyType,
                $noteValue,
                $applicableTaxRate,
                $rate ? ($rate.'%') : 0,
                $taxableAmt,
                $cess,
            ];
        }

        $rows[] = ['Summary For CDNR(9B)', '','','','','','','','','','','','Help'];
        $rows[] = ['No. of Recipients', '', 'No. of Notes','', '', '', '','', 'Total Noted Value','','','Total Taxable Value', 'Total Cess'];
        $rows[] = [count($uniqueRecipients), '', $noteCount, '', '', '', '','', $totalNoteValue,'','', $totalTaxableValue, $totalCess];
        $rows[] = [
            'GSTIN/UIN of Recipient',
            'Receiver Name',
            'Note Number',
            'Note Date',
            'Note Type',
            'Place Of Supply',
            'Reverse Charge',
            'Note Supply Type',
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
        return 'CDNR';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ✅ Merge Main Header Cells
                $sheet->mergeCells('A1:L1');
 
                $sheet->getCell('M1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
                $sheet->getStyle('M1')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0000FF'],
                        'underline' => 'single',
                  
                    ],
                ]);

                // ✅ Main Header Row (Row 1)
                $sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Sub Header Row (Row 2)
                $sheet->getStyle('A2:M2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']], // Light blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Column Header Row (Row 4)
                $sheet->getStyle('A4:M4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']], // Orange
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Borders for Header Area
                $sheet->getStyle('A1:M4')->applyFromArray([
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
                $sheet->getStyle('A1:M4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:M4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
