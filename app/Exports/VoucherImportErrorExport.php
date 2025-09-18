<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VoucherImportErrorExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $errorRows;

    public function __construct($errorRows)
    {
        $this->errorRows = $errorRows;
    }

    public function collection()
    {
        return $this->errorRows;
    }

    public function headings(): array
    {
        return [
            'Row Number',
            'Ledger Code',
            'Ledger Name',
            'Group ID',
            'Group Name',
            'Debit Amount',
            'Credit Amount',
            'Cost Center ID',
            'Cost Center Name',
            'Remark',
            'Errors'
        ];
    }

    public function map($row): array
    {
        $errors = is_array($row->reason) ? implode('; ', $row->reason) : $row->reason;
        
        return [
            $row->row_number,
            $row->ledger_code,
            $row->ledger_name,
            $row->group_id,
            $row->group_name,
            $row->debit_amount,
            $row->credit_amount,
            $row->cost_center_id,
            $row->cost_center_name,
            $row->remark,
            $errors
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
            
            // Style the error column with red background
            'K' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFFFE6E6',
                    ],
                ],
            ],
        ];
    }
}
