<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\Organization;
use App\Helpers\Helper;
class BomWithoutAttributesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * Query to fetch BOM attributes without values.
     */
    public function query()
    {
            $user = Helper::getAuthenticatedUser();
            $organizationId = $user->organization_id;
            $organization = Organization::find($organizationId);

            $groupId   = $organization?->group_id ?? null;
            $companyId = $organization?->company_id ?? null;
        return DB::table('erp_bom_attributes')
            ->join('erp_boms', 'erp_bom_attributes.bom_id', '=', 'erp_boms.id')
            ->select(
                'erp_bom_attributes.item_code',
                'erp_bom_attributes.bom_id',
                'erp_boms.book_code as series',
                'erp_boms.document_number',
                'erp_boms.document_date'
            )
            ->where('erp_boms.company_id', $companyId)
            ->where('erp_boms.group_id', $groupId)
            ->where('erp_boms.organization_id', $organizationId)
            ->where('erp_boms.type', 'bom')
            ->where('erp_bom_attributes.type', 'D')
            ->where(function ($q) {
                $q->whereNull('erp_bom_attributes.attribute_value')
                  ->orWhere('erp_bom_attributes.attribute_value', '');
            })
            ->orderBy('erp_bom_attributes.bom_id');
    }

    /**
     * Map each row for Excel.
     */
    public function map($row): array
    {
        return [
            $row->item_code,
            $row->bom_id,
            $row->series,
            $row->document_number,
            \Carbon\Carbon::parse($row->document_date)->format('d/m/Y'),
        ];
    }

    /**
     * Add headings to the Excel sheet.
     */
    public function headings(): array
    {
        return ['Item Code', 'BOM ID', 'Series', 'Document Number', 'Document Date'];
    }

     public function chunkSize(): int
    {
        return 1000; 
    }
}
