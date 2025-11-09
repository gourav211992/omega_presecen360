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

class ExpaSheet implements  FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
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
        $totalShipping =0;
    

        foreach ($this->data as $item) {
            // Support both array and object structures
            $expType = is_array($item) ? ($item['exp_type'] ?? '') : ($item->exp_type ?? '');
            $invoiceNo = is_array($item) ? ($item['invoice_no'] ?? '') : ($item->invoice_no ?? '');
            $invoiceDate = is_array($item) ? ($item['invoice_date'] ?? '') : ($item->invoice_date ?? '');
            $revisedInvoiceNo = is_array($item) ? ($item['revised_invoice_no'] ?? null) : ($item->revised_invoice_no ?? null);
            $revisedInvoiceDate = is_array($item) ? ($item['revised_invoice_date'] ?? null) : ($item->revised_invoice_date ?? null);
            $invoiceAmt = is_array($item) ? ($item['invoice_amt'] ?? 0) : ($item->invoice_amt ?? 0);
            $portCode = is_array($item) ? ($item['port_code'] ?? '') : ($item->port_code ?? '');
            $shippingBillNo = is_array($item) ? ($item['shipping_bill_no'] ?? '') : ($item->shipping_bill_no ?? '');
            $shippingBillDate = is_array($item) ? ($item['shipping_bill_date'] ?? 0) : ($item->shipping_bill_date ?? 0);
      

            
            $rate = is_array($item) ? ($item['rate'] ?? 0) : ($item->rate  ?? 0);
            $taxableAmt = is_array($item) ? ($item['taxable_amt'] ?? 0) : ($item->taxable_amt ?? 0);
            $cess = is_array($item) ? ($item['cess'] ?? 0) : ($item->cess ?? 0);

            if (!empty($partyGstin)) {
                $uniqueRecipients[$partyGstin] = true;
            }

            $invoiceCount++;
            $totalInvoiceValue += $invoiceAmt;
            $totalTaxableValue += $taxableAmt;
            $totalCess += $cess;
            if(!empty($shippingBillNo)){
                $totalShipping++;
            }

            $dataRows[] = [
                $expType,
                $invoiceNo,
                $invoiceDate ? GeneralHelper::dateFormat3($invoiceDate) : '',
                $revisedInvoiceNo ,
                $revisedInvoiceDate ? GeneralHelper::dateFormat3($revisedInvoiceDate) : '',
                $invoiceAmt,
                $portCode,
                $shippingBillNo,
                $shippingBillDate ?  GeneralHelper::dateFormat3($shippingBillDate) : '',
               
                $rate ? ($rate . '%') : 0,
                $taxableAmt,
                $cess
            ];
        }
        
            // Row 1: Main Title
            $rows[] = ['Summary For EXPA', 'Original details','','Revised Details', '','','','', '', '', '', 'Help'];

            // Row 2: Sub Header Titles
            $rows[] = ['', 'No. of Invoices','','','', 'Total Invoice Value', '', 'No. of Shipping Bill', '','', 'Total Taxable Value', 'Total Cess'];

            // Row 3: Sub Header Values
            $rows[] = ['',$invoiceCount , '', '', '', $totalInvoiceValue, '', $totalShipping, '', '',  $totalTaxableValue, $totalCess];

            // Row 4: Column Headings
            $rows[] = [
                'Export Type',
                'Original Invoice Number',
                'Original Invoice date',
                'Revised Invoice Number',
                'Revised Invoice Date',
                'Invoice Value',
                'Port Code',
                'Shipping Bill Number',
                'Shipping Bill Date',
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
        return 'EXPA';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ✅ Merge Main Header Cells
                $sheet->mergeCells('B1:C1'); // Summary
                $sheet->mergeCells('D1:K1'); // Summary

                $sheet->getCell('L1')->getHyperlink()->setUrl("sheet://'Help Instructions'!C18");

                // Optional styling for the link
                $sheet->getStyle('L1')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0000FF'],
                        'underline' => 'single',
                  
                    ],
                ]);

               // $sheet->mergeCells('O1:O1'); // HELP

    
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
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E75B6']], 
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

                // ✅ Adjust Row Heights
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(4)->setRowHeight(20);

                // ✅ Center Align All Headers
                $sheet->getStyle('A1:L4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:L4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
   
}
