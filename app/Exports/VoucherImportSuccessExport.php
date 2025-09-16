<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VoucherImportSuccessExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $successRows;

    public function __construct($successRows)
    {
        $this->successRows = $successRows;
    }

    public function collection()
    {
        return $this->successRows;
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
            'Status'
        ];
    }

    public function map($row): array
    {
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
            'Success'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
            
            // Style the status column with green background
            'K' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFE6F7E6',
                    ],
                ],
            ],
        ];
    }
}
