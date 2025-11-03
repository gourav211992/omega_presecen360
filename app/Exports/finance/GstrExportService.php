<?php

namespace App\Exports\finance;

use App\Models\Finance\GstrCompiledData;
use App\Helpers\CommonHelper;
use App\Helpers\ConstantHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Common\OrganizationHelper;
use App\Exports\finance\gst_reports\GstReport;
use App\Models\ErpGstInvoiceType;

class GstrExportService
{
    /**
     * Export a single XLSX with multiple sheets for all active invoice types
     */
    public function exportAll($fileName, $request, $supplierGstin)
    {
        try {
            $startDate = Carbon::now()->startOfMonth();
            $endDate   = Carbon::now()->endOfMonth();

            if ($request->has('date_range') && $request->date_range != '') {
                $dates = explode(' to ', $request->date_range);
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate   = isset($dates[1])
                    ? Carbon::parse($dates[1])->endOfDay()
                    : Carbon::parse($dates[0])->endOfDay();
            }

            $organizationId = $request->organization_id ?? OrganizationHelper::getOrganizationId();

            // Fetch all active invoice types, optional name search
            $types = ErpGstInvoiceType::where(function ($q) use ($request) {
                    if ($request->search) {
                        $q->where('erp_gst_invoice_types.name', 'like', '%' . $request->search . '%');
                    }
                })
                ->where('status', ConstantHelper::ACTIVE)
                ->get();

            $payload = [];

            foreach ($types as $type) {
                $query = GstrCompiledData::where(function ($q) use ($request, $organizationId) {
                        if ($request->search) {
                            $q->where(function ($query) use ($request) {
                                $query->where('erp_gstr_compiled_data.party_name', 'like', '%' . $request->search . '%')
                                    ->orWhere('erp_gstr_compiled_data.party_gstin', 'like', '%' . $request->search . '%');
                            });
                        }
                        if ($request->group_id) {
                            $q->where('erp_gstr_compiled_data.group_id', $request->group_id);
                        }
                        if ($request->company_id) {
                            $q->where('erp_gstr_compiled_data.company_id', $request->company_id);
                        }
                        if ($organizationId) {
                            $q->where('erp_gstr_compiled_data.organization_id', $organizationId);
                        }
                    })
                    ->whereBetween('erp_gstr_compiled_data.invoice_date', [$startDate, $endDate])
                    ->where('supplier_gstin', $supplierGstin);

                $this->applyInvoiceTypeFilter($query, $type->code, $type->id);

                $data = $query->get()->toArray();
                
                if (!empty($data)) {
                    $payload[$type->code] = $data;
                }
            }

            if (empty($payload)) {
                throw new \Exception('No records found for any invoice type.');
            }

            $excelPath = 'temp/finance/gstr1/' . basename($fileName, '.xlsx') . '.xlsx';
            // Ensure directory on public disk
            Storage::disk('public')->makeDirectory('temp/finance/gstr1');

            Excel::store(new GstReport($payload), $excelPath, 'public');

            return [
                'status' => true,
                'message' => 'Export successful',
                'file' => $excelPath,
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => 'Export failed: ' . $th->getMessage(),
            ];
        }
    }

    /**
     * Apply filters, selects, and groupings depending on GST type
     */
    private function applyInvoiceTypeFilter($query, $invoiceType, $id)
    {
        switch ($invoiceType) {
            case CommonHelper::HSN_B2B:
                $typeIds = ErpGstInvoiceType::whereIn('code', CommonHelper::HSN_B2B_INVOICE_TYPES)
                    ->where('status', ConstantHelper::ACTIVE)
                    ->pluck('id');

                $query->select(
                    "erp_gstr_compiled_data.hsn_code",
                    "erp_gstr_compiled_data.description",
                    "erp_gstr_compiled_data.uqc",
                    "erp_gstr_compiled_data.qty",
                    DB::raw("SUM(erp_gstr_compiled_data.taxable_amt) as taxable_amt"),
                    DB::raw("erp_gstr_compiled_data.rate as rate"),
                    DB::raw("SUM(erp_gstr_compiled_data.sgst) as sgst"),
                    DB::raw("SUM(erp_gstr_compiled_data.cgst) as cgst"),
                    DB::raw("SUM(erp_gstr_compiled_data.igst) as igst"),
                    DB::raw("SUM(erp_gstr_compiled_data.cess) as cess"),
                    DB::raw("erp_gstr_compiled_data.invoice_amt as invoice_amt")
                )
                ->whereIn('invoice_type_id', $typeIds)
                ->whereNotNull('hsn_code')
                ->whereNotNull('uqc')
                ->groupBy('hsn_code', 'uqc', 'rate');
                break;

            case CommonHelper::HSN_B2C:
                $typeIds = ErpGstInvoiceType::whereIn('code', CommonHelper::HSN_B2C_INVOICE_TYPES)
                    ->where('status', ConstantHelper::ACTIVE)
                    ->pluck('id');

                $query->whereIn('invoice_type_id', $typeIds)
                ->whereNotNull('hsn_code')
                ->whereNotNull('uqc')
                ->groupBy('hsn_code', 'uqc');
                break;

            case CommonHelper::DOC:
                $typeIds = ErpGstInvoiceType::whereIn('code', CommonHelper::DOC_INVOICE_TYPES)
                    ->where('status', ConstantHelper::ACTIVE)
                    ->pluck('id');

                $query->whereIn('invoice_type_id', $typeIds)
                ->whereNotNull('invoice_id')
                ->groupBy('invoice_id');
                break;

            case CommonHelper::B2B:
                $query->select(
                    "erp_gstr_compiled_data.party_gstin",
                    "erp_gstr_compiled_data.party_name",
                    "erp_gstr_compiled_data.invoice_no",
                    "erp_gstr_compiled_data.invoice_date",
                    "erp_gstr_compiled_data.pos",
                    "erp_gstr_compiled_data.place_of_supply",
                    "erp_gstr_compiled_data.reverse_charge",
                    "erp_gstr_compiled_data.applicable_tax_rate",
                    "erp_gstr_compiled_data.invoice_type",
                    "erp_gstr_compiled_data.e_commerce_gstin",
                    DB::raw("SUM(erp_gstr_compiled_data.taxable_amt) as taxable_amt"),
                    DB::raw("erp_gstr_compiled_data.rate as rate"),
                    DB::raw("SUM(erp_gstr_compiled_data.sgst) as sgst"),
                    DB::raw("SUM(erp_gstr_compiled_data.cgst) as cgst"),
                    DB::raw("SUM(erp_gstr_compiled_data.igst) as igst"),
                    DB::raw("SUM(erp_gstr_compiled_data.cess) as cess"),
                    DB::raw("erp_gstr_compiled_data.invoice_amt as invoice_amt")
                )
                ->where('invoice_type_id', $id)
                ->whereNotNull('invoice_id')
                ->whereNotNull('invoice_no')
                ->whereNotNull('rate')
                ->groupBy('invoice_id', 'invoice_no', 'rate');
                break;

            default:
                $query->where('invoice_type_id', $id)
                ->whereNotNull('invoice_id')
                ->groupBy('invoice_id');
                break;
        }
    }
}
