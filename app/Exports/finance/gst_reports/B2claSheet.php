<?php

namespace App\Exports\finance\gst_reports;

use App\Helpers\GeneralHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class B2claSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
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
        $invoiceCount = 0;
        $totalInvoiceValue = 0;
        $totalTaxableValue = 0;
        $totalCess = 0;

        foreach ($this->data as $item) {
            $invoiceNo = is_array($item) ? ($item['invoice_no'] ?? '') : ($item->invoice_no ?? '');
            $invoiceDateRaw = is_array($item) ? ($item['invoice_date'] ?? null) : ($item->invoice_date ?? null);
            $invoiceDate = $invoiceDateRaw ? GeneralHelper::dateFormat3($invoiceDateRaw) : '';
            $pos = is_array($item) ? ($item['pos'] ?? '') : ($item->pos ?? '');
            $placeOfSupply = is_array($item) ? ($item['place_of_supply'] ?? '') : ($item->place_of_supply ?? '');
            $revisedNo = is_array($item) ? ($item['revised_invoice_no'] ?? '') : ($item->revised_invoice_no ?? '');
            $revisedDateRaw = is_array($item) ? ($item['revised_invoice_date'] ?? null) : ($item->revised_invoice_date ?? null);
            $revisedDate = $revisedDateRaw ? GeneralHelper::dateFormat3($revisedDateRaw) : '';
            $invoiceAmt = is_array($item) ? ($item['invoice_amt'] ?? 0) : ($item->invoice_amt ?? 0);
            $applicableTaxRate = is_array($item) ? ($item['applicable_tax_rate'] ?? 0) : ($item->applicable_tax_rate ?? 0);
            $rate = is_array($item) ? ($item['rate'] ?? 0) : ($item->rate ?? 0);
            $taxableAmt = is_array($item) ? ($item['taxable_amt'] ?? 0) : ($item->taxable_amt ?? 0);
            $cess = is_array($item) ? ($item['cess'] ?? 0) : ($item->cess ?? 0);
            $ecomGstin = is_array($item) ? ($item['e_commerce_gstin'] ?? '') : ($item->e_commerce_gstin ?? '');

            $invoiceCount += 1;
            $totalInvoiceValue += $invoiceAmt;
            $totalTaxableValue += $taxableAmt;
            $totalCess += $cess;

            $dataRows[] = [
                $invoiceNo,
                $invoiceDate,
                $pos && $placeOfSupply ? ($pos.'-'.$placeOfSupply) : ($placeOfSupply ?: ''),
                $revisedNo,
                $revisedDate,
                $invoiceAmt,
                $applicableTaxRate,
                $rate ? ($rate.'%') : 0,
                $taxableAmt,
                $cess,
                $ecomGstin,
            ];
        }

        $rows[] = ['Summary For B2CLA', 'Original details', '', 'Revised Details', '', '', '', '', '', '', 'Help'];
        $rows[] = ['No. of Invoices', '','', '','', 'Total Invoice Value', '','', 'Total Taxable Value', 'Total Cess', ''];
        $rows[] = [$invoiceCount, '','', '','', $totalInvoiceValue, '','', $totalTaxableValue, $totalCess, ''];
        $rows[] = [
            'Original Invoice Number',
            'Original Invoice date',
            'Original Place Of Supply',
            'Revised Invoice Number',
            'Revised Invoice date',
            'Invoice Value',
            'Applicable % of Tax Rate',
            'Rate',
            'Taxable Value',
            'Cess Amount',
            'E-Commerce GSTIN'
        ];

        return array_merge($rows, $dataRows);
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'B2CLA';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ✅ Merge Main Header Cells (11 columns: A..K)
                $sheet->mergeCells('B1:C1');
                $sheet->mergeCells('D1:J1');

                $sheet->getCell('k1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
                $sheet->getStyle('k1')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0000FF'],
                        'underline' => 'single',
                  
                    ],
                ]);
                // ✅ Merge Sub Headers
                // $sheet->mergeCells('A2:C2'); // No. of Invoices
                // $sheet->mergeCells('D2:G2'); // Total Invoice Value
                // $sheet->mergeCells('H2:I2'); // Total Taxable Value
                // $sheet->mergeCells('J2:K2'); // Total Cess

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
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']], 
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                $sheet->getStyle('D1:K1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Sub Header Row (Row 2)
                $sheet->getStyle('A2:K2')->applyFromArray([
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


                $sheet->getStyle('D4:K4')->applyFromArray([
                    'font' => ['bold' => true,'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']], 
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
