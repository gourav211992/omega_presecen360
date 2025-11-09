<?php

namespace App\Exports\finance\gst_reports;

use App\Helpers\GeneralHelper;
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

class B2clSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
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
            $invoiceAmt = is_array($item) ? ($item['invoice_amt'] ?? 0) : ($item->invoice_amt ?? 0);
            $pos = is_array($item) ? ($item['pos'] ?? '') : ($item->pos ?? '');
            $placeOfSupply = is_array($item) ? ($item['place_of_supply'] ?? '') : ($item->place_of_supply ?? '');
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
                $invoiceAmt,
                $pos && $placeOfSupply ? ($pos.'-'.$placeOfSupply) : ($placeOfSupply ?: ''),
                $applicableTaxRate,
                $rate ? ($rate.'%') : 0,
                $taxableAmt,
                $cess,
                $ecomGstin,
            ];
        }

        $rows[] = ['Summary For B2CL(5)', '', '', '', '', '', '', '', 'Help'];
        $rows[] = ['No. of Invoices', '', 'Total Invoice Value','', '','', 'Total Taxable Value', 'Total Cess', ''];
      //  $rows[] = ['No. of Invoices', '', 'Total Invoice Value', '', 'Total Taxable Value', '', 'Total Cess', '', ''];

        $rows[] = [$invoiceCount, '', $totalInvoiceValue, '', '','', $totalTaxableValue, $totalCess, ''];
        //$rows[] = [$invoiceCount, '', $totalInvoiceValue, '', $totalTaxableValue, '', $totalCess, '', ''];

        $rows[] = [
            'Invoice Number',
            'Invoice date',
            'Invoice Value',
            'Place Of Supply',
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
        return 'B2CL';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ✅ Merge Main Header Cells (9 columns: A..I)
                $sheet->mergeCells('A1:H1');

                $sheet->getCell('I1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
                $sheet->getStyle('I1')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0000FF'],
                        'underline' => 'single',
                  
                    ],
                ]);
         

                // ✅ Main Header Row (Row 1)
                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E78']], // Dark blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Sub Header Row (Row 2)
                $sheet->getStyle('A2:I2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']], // Light blue
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);


                // ✅ Column Header Row (Row 4)
                $sheet->getStyle('A4:I4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F4B084']], // Orange
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);

                // ✅ Borders for Header Area
                $sheet->getStyle('A1:I4')->applyFromArray([
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
                $sheet->getStyle('A1:I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:I4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
