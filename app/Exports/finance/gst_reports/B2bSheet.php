<?php

namespace App\Exports\finance\gst_reports;

use App\Helpers\GeneralHelper;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class B2bSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
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
        $invoiceCount = 0;
        $totalInvoiceValue = 0;
        $totalTaxableAmt = 0;
        $totalCessAmount = 0;

        // Data rows as per prepareB2bData ordering
        foreach ($this->data as $item) {
            // Support both array and object shapes
            $partyGstin = is_array($item) ? ($item['party_gstin'] ?? '') : ($item->party_gstin ?? '');
            $partyName = is_array($item) ? ($item['party_name'] ?? '') : ($item->party_name ?? '');
            $invoiceNo = is_array($item) ? ($item['invoice_no'] ?? '') : ($item->invoice_no ?? '');
            $invoiceDateRaw = is_array($item) ? ($item['invoice_date'] ?? null) : ($item->invoice_date ?? null);
            $invoiceDate = $invoiceDateRaw ? GeneralHelper::dateFormat3($invoiceDateRaw) : '';
            $invoiceAmt = is_array($item) ? ($item['invoice_amt'] ?? 0) : ($item->invoice_amt ?? 0);
            $pos = is_array($item) ? ($item['pos'] ?? '') : ($item->pos ?? '');
            $placeOfSupply = is_array($item) ? ($item['place_of_supply'] ?? '') : ($item->place_of_supply ?? '');
            $reverseCharge = is_array($item) ? ($item['reverse_charge'] ?? 0) : ($item->reverse_charge ?? 0);
            $applicableTaxRate = is_array($item) ? ($item['applicable_tax_rate'] ?? 0) : ($item->applicable_tax_rate ?? 0);
            $invoiceType = is_array($item) ? ($item['invoice_type'] ?? '') : ($item->invoice_type ?? '');
            $ecomGstin = is_array($item) ? ($item['e_commerce_gstin'] ?? '') : ($item->e_commerce_gstin ?? '');
            $rate = is_array($item) ? ($item['rate'] ?? 0) : ($item->rate ?? 0);
            $taxableAmt = is_array($item) ? ($item['taxable_amt'] ?? 0) : ($item->taxable_amt ?? 0);
            $cess = is_array($item) ? ($item['cess'] ?? 0) : ($item->cess ?? 0);
               
            if (!empty($partyGstin)) {
                $uniqueRecipients[$partyGstin] = true;
            }
            $invoiceCount += 1;

            $totalInvoiceValue += $invoiceAmt;
            $totalTaxableAmt += $taxableAmt;
            $totalCessAmount += $cess;


            $dataRows[] = [
                $partyGstin,
                $partyName,
                $invoiceNo,
                $invoiceDate,
                $invoiceAmt,
                $pos && $placeOfSupply ? ($pos.'-'.$placeOfSupply) : ($placeOfSupply ?: ''),
                $reverseCharge,
                $applicableTaxRate,
                $invoiceType,
                $ecomGstin,
                $rate ? ($rate.'%') : 0,
                $taxableAmt,
                $cess,
            ];
        }

        // Summary + subheaders + column headings
        $rows[] = ['Summary For B2B, SEZ, DE (4A, 4B, 6B, 6C)', '', '', '', '', '', '', '', '', '', '', '', 'Help'];
        $rows[] = ['No. of Recipients', '', 'No. of Invoices', '', 'Total Invoice Value', '','','','','','', 'Total Taxable Value', 'Total Cess'];
        $rows[] = [count($uniqueRecipients), '', $invoiceCount, '', $totalInvoiceValue, '', '', '', '', '', '', $totalTaxableAmt, $totalCessAmount];
        $rows[] = ['GSTIN/UIN of Recipient','Receiver Name','Invoice Number','Invoice date','Invoice Value','Place Of Supply','Reverse Charge','Applicable % of Tax Rate','Invoice Type','E-Commerce GSTIN','Rate','Taxable Value','Cess Amount'];

        return array_merge($rows, $dataRows);
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'B2B, SEZ, DE';
    }

  public function registerEvents(): array
{
    return [
        AfterSheet::class => function(AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();

            $sheet->mergeCells('A1:L1'); // 13 columns wide

            // $sheet->mergeCells('A2:B2'); // No. of Recipients
            // $sheet->mergeCells('C2:D2'); // No. of Invoices
            // $sheet->mergeCells('E2:K2'); // Total Invoice Value span
            // $sheet->mergeCells('L2:M2'); // Total Taxable Value & Cess

            $sheet->getCell('M1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
            $sheet->getStyle('M1')->applyFromArray([
                'font' => [
                    'color' => ['rgb' => '0000FF'],
                    'underline' => 'single',
                
                ],
            ]);

            $sheet->getStyle('A1:M1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
            ]);

            $sheet->getStyle('A2:M2')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $sheet->getStyle('A4:M4')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
            ]);

            $sheet->getStyle('A1:M4')->applyFromArray([
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
