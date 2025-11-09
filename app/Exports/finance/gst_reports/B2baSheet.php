<?php

namespace App\Exports\finance\gst_reports;

use App\Helpers\GeneralHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


class B2baSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
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
        $totalTaxableValue = 0;
        $totalCess = 0;
        
        foreach ($this->data as $item) {
            // Support both array and object structures
            $partyGstin = is_array($item) ? ($item['party_gstin'] ?? '') : ($item->party_gstin ?? '');
            $partyName = is_array($item) ? ($item['party_name'] ?? '') : ($item->party_name ?? '');
            $originalInvoiceNo = is_array($item) ? ($item['invoice_no'] ?? '') : ($item->invoice_no ?? '');
            $originalInvoiceDate = is_array($item) ? ($item['invoice_date'] ?? null) : ($item->invoice_date ?? null);
            $revisedInvoiceNo = is_array($item) ? ($item['revised_invoice_date'] ?? '') : ($item->revised_invoice_date ?? '');
            $revisedInvoiceDate = is_array($item) ? ($item['revised_invoice_date'] ?? null) : ($item->revised_invoice_date ?? null);
            $invoiceAmt = is_array($item) ? ($item['invoice_amt'] ?? 0) : ($item->invoice_amt ?? 0);
            $placeOfSupply = is_array($item) ? ($item['place_of_supply'] ?? '') : ($item->place_of_supply ?? '');
            $pos = is_array($item) ? ($item['pos'] ?? '') : ($item->pos ?? '');
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

            $invoiceCount++;
            $totalInvoiceValue += $invoiceAmt;
            $totalTaxableValue += $taxableAmt;
            $totalCess += $cess;

            $dataRows[] = [
                $partyGstin,
                $partyName,
                $originalInvoiceNo,
                $originalInvoiceDate ? GeneralHelper::dateFormat3($originalInvoiceDate) : '',
                $revisedInvoiceNo,
                $revisedInvoiceDate ? GeneralHelper::dateFormat3($revisedInvoiceDate) : '',
                $invoiceAmt,
                $pos && $placeOfSupply ? ($pos . '-' . $placeOfSupply) : ($placeOfSupply ?: ''),
                $reverseCharge,
                $applicableTaxRate,
                $invoiceType,
                $ecomGstin,
                $rate ? ($rate . '%') : 0,
                $taxableAmt,
                $cess,
            ];
        }
        
            // Row 1: Main Title
            $rows[] = ['Summary For B2BA', 'Original details', '','','Revised Details', '','','','', '', '', '', '', '', 'Help'];

            // Row 2: Sub Header Titles
            $rows[] = ['No. of Recipients', '', 'No. of Invoices', '','','', 'Total Invoice Value', '', '', '', '', '', '', 'Total Taxable Value', 'Total Cess'];

            // Row 3: Sub Header Values
            $rows[] = [count($uniqueRecipients), '', $invoiceCount, '', '', '', $totalInvoiceValue, '', '', '', '', '', '', $totalTaxableValue, $totalCess];

            // Row 4: Column Headings
            $rows[] = [
                'GSTIN/UIN of Recipient',
                'Receiver Name',
                'Original Invoice Number',
                'Original Invoice Date',
                'Revised Invoice Number',
                'Revised Invoice Date',
                'Invoice Value',
                'Place Of Supply',
                'Reverse Charge',
                'Applicable % of Tax Rate',
                'Invoice Type',
                'E-Commerce GSTIN',
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
        return 'B2BA';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ✅ Merge Main Header Cells
                $sheet->mergeCells('A1:A1'); // Summary
                $sheet->mergeCells('B1:D1'); // Original details
                $sheet->mergeCells('E1:N1'); // Revised details
               // $sheet->mergeCells('O1:O1'); // HELP

    
                $sheet->getStyle('A1:A1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('B1:D1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '121111']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']], // SKY BLUE blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('E1:O1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                
                $sheet->getStyle('A2:O2')->applyFromArray([
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

                $sheet->getStyle('E4:O4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']], // Orange
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Borders for Header Area
                $sheet->getStyle('A1:O4')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getCell('O1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
                $sheet->getStyle('O1')->applyFromArray([
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
                $sheet->getStyle('A1:O4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:O4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
