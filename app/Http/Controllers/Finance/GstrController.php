<?php

namespace App\Http\Controllers\Finance;

use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\ErpGstInvoiceType;
use App\Models\Finance\GstrCompiledData;
use App\Models\Organization;
use App\Models\OrganizationCompany;
use App\Models\OrganizationGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\finance\GstrDetailExport;
use App\Helpers\Common\OrganizationHelper;
use App\Helpers\CommonHelper;
use App\Helpers\GstrHelper;
use App\Helpers\Helper;
use Illuminate\Pagination\LengthAwarePaginator;
class GstrController extends Controller
{

    public function index(Request $request){
        $pageLengths = ConstantHelper::PAGE_LENGTHS;
        $length = $request->length ? $request->length : ConstantHelper::PAGE_LENGTH_10;

        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $gstin = $organization->gst_number;

        $masterData = self::masterData();
        $date = self::getStartEndDate($request);

        $types = ErpGstInvoiceType::select('erp_gst_invoice_types.id', 'erp_gst_invoice_types.name', 'erp_gst_invoice_types.code')
            ->where(function($q) use($request){
                if($request->search){
                    $q->where('erp_gst_invoice_types.name', 'like', '%' . $request->search . '%');
                }
            })
            ->where('status',ConstantHelper::ACTIVE)
            ->get();

        $invoiceData = [];
        $filteredTypes = collect();
        foreach($types as $type){
            $summary = $this->getSummaryData($request, $type->id, $type->code, $gstin);

            // Add only if invoice_count > 0
            if($summary['invoice_count'] > 0){
                $invoiceData[$type->code] = $summary;
                $filteredTypes->push($type);
            }
        }

        return view('finance.gstr.index',[
            'pageLengths' => $pageLengths,
            'invoiceData' => $invoiceData,
            'startDate' => $date['startDate'],
            'endDate' => $date['endDate'],
            'groups' => $masterData['groups'],
            'organizationData' => $masterData['organizations'],
            'companies' => $masterData['companies'],
            'types' => $filteredTypes,
            'invoiceTypes' => $masterData['types'],
        ]);
    }

    private function getSummaryData($request, $typeId, $type, $gstin){
        switch ($type) {
            case 'hsnb2b':
                return self::getHsnB2BSummary($request, $gstin);
            case 'hsnb2c':
                return self::getHsnB2CSummary($request, $gstin);
            case 'doc_issue':
                return self::getDocSummary($request, $gstin);
            default:
                return self::getInvoiceSummary($request, $typeId, $gstin);
        }
    }

    private function getHsnB2BSummary($request, $gstin){
        // Get Hsn Type Ids
        $masterData = self::masterData();
        $invoiceTypesIds = $masterData['hsnB2bTypeIds'];

        // Get Start Date/ End Date
        $date = self::getStartEndDate($request);
        $startDate = $date['startDate'];
        $endDate = $date['endDate'];

        $data = GstrCompiledData::where(function ($query) use ($request) {
                    $this->filter($request,$query);
                })
                ->whereIn('invoice_type_id',$invoiceTypesIds)
                ->whereBetween("erp_gstr_compiled_data.invoice_date", [$startDate, $endDate])
                ->where("erp_gstr_compiled_data.supplier_gstin", $gstin)
                ->select(
                    DB::raw("SUM(erp_gstr_compiled_data.taxable_amt) as taxable_amt"),
                    DB::raw("SUM(erp_gstr_compiled_data.rate) as gst_rate"),
                    DB::raw("SUM(erp_gstr_compiled_data.sgst) as sgst"),
                    DB::raw("SUM(erp_gstr_compiled_data.cgst) as cgst"),
                    DB::raw("SUM(erp_gstr_compiled_data.igst) as igst"),
                    DB::raw("SUM(erp_gstr_compiled_data.cess) as cess"),
                    DB::raw("erp_gstr_compiled_data.invoice_amt as invoice_amt")
                )
                ->whereNotNull('hsn_code')
                ->groupBy('hsn_code')
                ->get()
                ->toArray();

        return [
            'taxable_amt' => array_sum(array_column($data, 'taxable_amt')),
            'rate' => array_sum(array_column($data, 'rate')),
            'sgst' => array_sum(array_column($data, 'sgst')),
            'cgst' => array_sum(array_column($data, 'cgst')),
            'igst' => array_sum(array_column($data, 'igst')),
            'cess' => array_sum(array_column($data, 'cess')),
            'invoice_amt' => array_sum(array_column($data, 'invoice_amt')),
            'invoice_count' => count($data)
        ];
    }

    private function getHsnB2CSummary($request, $gstin){
        // Get Hsn Type Ids
        $masterData = self::masterData();
        $invoiceTypesIds = $masterData['hsnB2cTypeIds'];

        // Get Start Date/ End Date
        $date = self::getStartEndDate($request);
        $startDate = $date['startDate'];
        $endDate = $date['endDate'];

        $data = GstrCompiledData::where(function ($query) use ($request) {
                    $this->filter($request,$query);
                })
                ->whereIn('invoice_type_id',$invoiceTypesIds)
                ->whereBetween("erp_gstr_compiled_data.invoice_date", [$startDate, $endDate])
                ->where("erp_gstr_compiled_data.supplier_gstin", $gstin)
                ->select(
                    DB::raw("SUM(erp_gstr_compiled_data.taxable_amt) as taxable_amt"),
                    DB::raw("SUM(erp_gstr_compiled_data.rate) as gst_rate"),
                    DB::raw("SUM(erp_gstr_compiled_data.sgst) as sgst"),
                    DB::raw("SUM(erp_gstr_compiled_data.cgst) as cgst"),
                    DB::raw("SUM(erp_gstr_compiled_data.igst) as igst"),
                    DB::raw("SUM(erp_gstr_compiled_data.cess) as cess"),
                    DB::raw("erp_gstr_compiled_data.invoice_amt as invoice_amt")
                )
                ->whereNotNull('hsn_code')
                ->groupBy('hsn_code')
                ->get()
                ->toArray();

        return [
            'taxable_amt' => array_sum(array_column($data, 'taxable_amt')),
            'rate' => array_sum(array_column($data, 'rate')),
            'sgst' => array_sum(array_column($data, 'sgst')),
            'cgst' => array_sum(array_column($data, 'cgst')),
            'igst' => array_sum(array_column($data, 'igst')),
            'cess' => array_sum(array_column($data, 'cess')),
            'invoice_amt' => array_sum(array_column($data, 'invoice_amt')),
            'invoice_count' => count($data)
        ];
    }

    private function getDocSummary($request, $gstin){
        // Get Doc Type Ids
        $masterData = self::masterData();
        $invoiceTypesIds = $masterData['docTypeIds'];

        // Get Start Date/ End Date
        $date = self::getStartEndDate($request);
        $startDate = $date['startDate'];
        $endDate = $date['endDate'];

        $data = GstrCompiledData::where(function ($query) use ($request) {
                    $this->filter($request,$query);
                })
                ->whereIn('invoice_type_id',$invoiceTypesIds)
                ->whereBetween("erp_gstr_compiled_data.invoice_date", [$startDate, $endDate])
                ->where("erp_gstr_compiled_data.supplier_gstin", $gstin)
                ->whereNotNull('invoice_id')
                ->groupBy('invoice_id')
                ->get()
                ->toArray();

        return [
            'taxable_amt' => 0,
            'rate' => 0,
            'sgst' => 0,
            'cgst' => 0,
            'igst' => 0,
            'cess' => 0,
            'invoice_amt' => 0,
            'invoice_count' => count($data)
        ];
    }

    private function getInvoiceSummary($request, $typeId, $gstin){
        // Get Start Date/ End Date
        $date = self::getStartEndDate($request);
        $startDate = $date['startDate'];
        $endDate = $date['endDate'];

        $data = GstrCompiledData::where(function ($query) use ($request) {
                    $this->filter($request,$query);
                })
                ->where('invoice_type_id',$typeId)
                ->whereBetween("erp_gstr_compiled_data.invoice_date", [$startDate, $endDate])
                ->where("erp_gstr_compiled_data.supplier_gstin", $gstin)
                ->select(
                    DB::raw("SUM(erp_gstr_compiled_data.taxable_amt) as taxable_amt"),
                    DB::raw("SUM(erp_gstr_compiled_data.rate) as gst_rate"),
                    DB::raw("SUM(erp_gstr_compiled_data.sgst) as sgst"),
                    DB::raw("SUM(erp_gstr_compiled_data.cgst) as cgst"),
                    DB::raw("SUM(erp_gstr_compiled_data.igst) as igst"),
                    DB::raw("SUM(erp_gstr_compiled_data.cess) as cess"),
                    DB::raw("erp_gstr_compiled_data.invoice_amt as invoice_amt")
                )
                ->whereNotNull('invoice_id')
                ->groupBy('invoice_id')
                ->get()
                ->toArray();

        return [
            'taxable_amt' => array_sum(array_column($data, 'taxable_amt')),
            'rate' => array_sum(array_column($data, 'rate')),
            'sgst' => array_sum(array_column($data, 'sgst')),
            'cgst' => array_sum(array_column($data, 'cgst')),
            'igst' => array_sum(array_column($data, 'igst')),
            'cess' => array_sum(array_column($data, 'cess')),
            'invoice_amt' => array_sum(array_column($data, 'invoice_amt')),
            'invoice_count' => count($data)
        ];
    }

    public function json(Request $request){
        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $supplierGstin = $organization->gst_number;

        $startDate = Carbon::now()->startOfMonth(); // Start of the current month
        $endDate = Carbon::now()->endOfMonth();

        // Check if there's an applied date filter
        if ($request->has('date_range') && $request->date_range != '') {
            $dates = explode(' to ', $request->date_range);
            $startDate = $dates[0] ? Carbon::parse($dates[0])->startOfDay() : null;
            $endDate = isset($dates[1]) ? Carbon::parse($dates[1])->startOfDay():  Carbon::parse($dates[0])->startOfDay();
        }

        $financialPeriod = date('mY');
        // $financialPeriod = self::currentFinancialYear();

        // Fetch all invoice types
        $gstrInvoiceTypes = ErpGstInvoiceType::where(function($q) use($request){
            if($request->search){
                $q->where('name', 'like', '%' . $request->search . '%');
            }
        })
        ->where('status',ConstantHelper::ACTIVE)
        ->get();

        // Initialize the response array
        $arr = [
            "gstin" => $supplierGstin,
            "fp" => $financialPeriod,
            "version" => "GST3.2.1",
            "hash" => "hash"
        ];

        // Define the invoice types that should be grouped under "ecoma"
        $ecomaTypes = ['ecoab2b', 'ecoab2c', 'ecoaurp2b', 'ecoaurp2c'];
        $ecomTypes = ['ecob2b', 'ecob2c', 'ecourp2b', 'ecourp2c'];

        // Loop through each invoice type and process data
        foreach ($gstrInvoiceTypes as $invoiceType) {
            $invoiceTypeName = strtolower($invoiceType->name);

            $gstrCompiledData = GstrCompiledData::where(function ($query) use ($request) {
                    $this->filter($request,$query);
                })
                ->where('invoice_type_id', $invoiceType->id)
                ->where('supplier_gstin', $supplierGstin)
                ->whereBetween('erp_gstr_compiled_data.invoice_date', [$startDate, $endDate])
                ->whereNotNull('erp_gstr_compiled_data.invoice_id')
                ->groupBy('erp_gstr_compiled_data.invoice_id')
                ->get();


            if ($gstrCompiledData->isEmpty()) {
                continue; // Skip if no data for this type
            }

            if (in_array($invoiceTypeName, $ecomaTypes)) {
                $arr['ecoma'] = array_merge($arr['ecoma'] ?? [], GstrHelper::prepareData($gstrCompiledData, $invoiceTypeName));
            }elseif (in_array($invoiceTypeName, $ecomTypes)) {
                $arr['ecom'] = array_merge($arr['ecom'] ?? [], GstrHelper::prepareData($gstrCompiledData, $invoiceTypeName));
            }else{
                $arr[$invoiceTypeName] = GstrHelper::prepareData($gstrCompiledData, $invoiceTypeName);
            }

        }

        // Convert JSON to a pretty format
        $jsonContent = json_encode($arr, JSON_PRETTY_PRINT);
        $fileName = $supplierGstin.'_gstr1.json';

        return response($jsonContent)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
    }

    public function details(Request $request,$id){
        $pageLengths = ConstantHelper::PAGE_LENGTHS;
        $length = $request->length ? $request->length : ConstantHelper::PAGE_LENGTH_10;

        $masterData = self::masterData();

        // Get Start Date/ End Date
        $date = self::getStartEndDate($request);
        $startDate = $date['startDate'];
        $endDate = $date['endDate'];

        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $supplierGstin = $organization->gst_number;

        $type = ErpGstInvoiceType::where('id',$id)->first();
        // $gstrData = $this->getInvoiceDetail($request, $type, $supplierGstin);
        $query = GstrCompiledData::where(function($query) use($request){
                        $this->filter($request,$query);

                        if($request->has('search')){
                            $query->where('erp_gstr_compiled_data.party_name', 'like', '%' . $request->search . '%')
                                ->orWhere('erp_gstr_compiled_data.party_gstin', 'like', '%' . $request->search . '%');
                        }

                    })
                    ->whereBetween('erp_gstr_compiled_data.invoice_date', [$startDate, $endDate])
                    ->where('supplier_gstin', $supplierGstin);

        // Apply invoice type filters
        $this->applyInvoiceTypeFilter($query, $type->code, $id);

        $gstrData = $query->paginate($length);

        return view('finance.gstr.detail',[
            'pageLengths' => $pageLengths,
            'gstrData' => $gstrData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'type' => $type,
            'groups' => $masterData['groups'],
            'organizationData' => $masterData['organizations'],
            'companies' => $masterData['companies'],
        ]);
    }

    private function applyInvoiceTypeFilter($query, $type, $id) {
        switch ($type) {
            case CommonHelper::HSN_B2B:
                $typeIds = ErpGstInvoiceType::whereIn('code', CommonHelper::HSN_B2B_INVOICE_TYPES)
                        ->where('status',ConstantHelper::ACTIVE)
                        ->pluck('id');
                $query->whereIn('invoice_type_id', $typeIds)
                        ->whereNotNull('hsn_code')
                        ->whereNotNull('uqc')
                        ->groupBy('hsn_code','uqc', 'rate');
                break;

            case CommonHelper::HSN_B2C:
                $typeIds = ErpGstInvoiceType::whereIn('code', CommonHelper::HSN_B2C_INVOICE_TYPES)
                        ->where('status',ConstantHelper::ACTIVE)
                        ->pluck('id');
                $query->whereIn('invoice_type_id', $typeIds)
                        ->whereNotNull('hsn_code')
                        ->whereNotNull('uqc')
                        ->groupBy('hsn_code','uqc');
                break;

            case CommonHelper::DOC:
                $typeIds = ErpGstInvoiceType::whereIn('code', CommonHelper::DOC_INVOICE_TYPES)
                        ->where('status',ConstantHelper::ACTIVE)
                        ->pluck('id');
                $query->whereIn('invoice_type_id', $typeIds)
                        ->whereNotNull('invoice_id')
                        ->groupBy('invoice_id');
                break;

            case CommonHelper::B2B:
                $query->where('invoice_type_id', $id)
                    ->whereNotNull('invoice_id')
                    ->whereNotNull('invoice_no')
                    ->whereNotNull('rate')
                    ->groupBy('invoice_id','invoice_no','rate');
                break;

            default:
                $query->where('invoice_type_id', $id)
                        ->whereNotNull('invoice_id')
                        ->groupBy('invoice_id');
                break;
        }
    }

    private function filter($request,$query){
        $organizationId = $request->organization_id ?? OrganizationHelper::getOrganizationId();

        if($request->group_id){
            $query->where('erp_gstr_compiled_data.group_id', $request->group_id);
        }

        if($request->company_id){
            $query->where('erp_gstr_compiled_data.company_id', $request->company_id);
        }

        if($organizationId){
            $query->where('erp_gstr_compiled_data.organization_id', $organizationId);
        }

        return $query;

    }

    private function masterData(){
        $groups = OrganizationGroup::select('id','name')->get();
        $organizations = Organization::select('id','name')->get();
        $companies = OrganizationCompany::select('id','name')->get();
        $types = ErpGstInvoiceType::where('status',ConstantHelper::ACTIVE)->get();

        $hsnB2bTypeIds = ErpGstInvoiceType::whereIn('code',CommonHelper::HSN_B2B_INVOICE_TYPES)
                    ->where('status',ConstantHelper::ACTIVE)
                    ->pluck('id')
                    ->toArray();

        $hsnB2cTypeIds = ErpGstInvoiceType::whereIn('code',CommonHelper::HSN_B2C_INVOICE_TYPES)
                    ->where('status',ConstantHelper::ACTIVE)
                    ->pluck('id')
                    ->toArray();

        $docTypeIds = ErpGstInvoiceType::whereIn('code',CommonHelper::DOC_INVOICE_TYPES)
                    ->where('status',ConstantHelper::ACTIVE)
                    ->pluck('id')
                    ->toArray();

        return [
            'groups' => $groups,
            'organizations' => $organizations,
            'companies' => $companies,
            'types' => $types,
            'docTypeIds' => $docTypeIds,
            'hsnB2bTypeIds' => $hsnB2bTypeIds,
            'hsnB2cTypeIds' => $hsnB2cTypeIds,
        ];
    }

    private function getStartEndDate($request){
        $startDate = Carbon::now()->startOfMonth(); // Start of the current month
        $endDate = Carbon::now()->endOfMonth();

        // Check if there's an applied date filter
        if ($request->has('date_range') && $request->date_range != '') {
            $dates = explode(' to ', $request->date_range);
            $startDate = $dates[0] ? Carbon::parse($dates[0])->startOfDay() : null;
            $endDate = isset($dates[1]) ? Carbon::parse($dates[1])->startOfDay():  Carbon::parse($dates[0])->startOfDay();
        }

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];

    }

    public function detailCsv(Request $request, $id){
        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $gstin = $organization->gst_number;

        // ✅ Ensure directory exists with correct permissions
        $directoryPath = public_path('temp/finance/gstr1');
        if (!file_exists($directoryPath)) {
            mkdir($directoryPath, 0775, true);
            chmod($directoryPath, 0775);
        }

        $gstrExport = new GstrDetailExport();
        if ($id === 'all') {
            $types = ErpGstInvoiceType::where(function($q) use($request){
                        if($request->search){
                            $q->where('erp_gst_invoice_types.name', 'like', '%' . $request->search . '%');
                        }
                    })
                    ->where('status',ConstantHelper::ACTIVE)
                    ->get();

            if ($types->isEmpty()) {
                return back()->with('error', 'No invoice types found to export.');
            }

            $zipFileName = "temp/finance/gstr1/{$gstin}_all_csvs.zip";
            $zipPath = public_path($zipFileName);
            $zip = new \ZipArchive;

            if (file_exists($zipPath)) {
                unlink($zipPath); // remove old file if exists
            }

            if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                $filesAdded = 0;
                foreach ($types as $type) {
                    $fileName = "temp/finance/gstr1/{$type->name}.csv";
                    $result = $gstrExport->export($fileName, $request, $type->id, $type->name, $gstin);

                    if ($result !== false && file_exists(public_path($fileName))) {
                        $zip->addFile(public_path($fileName), $type->name . '.csv');
                        $filesAdded++;
                    }
                    // $zip->addFile(public_path($fileName), $type->name . '.csv');
                }

                $zip->close();

                if ($filesAdded > 0) {
                    return response()->download($zipPath);
                }

                return response()->download($zipPath);
            } else {
                return back()->with('error', 'Could not create ZIP file');
            }

        }else{
            $type = ErpGstInvoiceType::where('id',$id)->first();
            if (!$type) {
                return back()->with('error', 'Invalid export type selected.');
            }

            $fileName = "temp/finance/gstr1/".$gstin.'_'.$type->code.".csv";
            $result = $gstrExport->export($fileName, $request, $id, $type->code, $gstin);
            if ($result === false || !file_exists(public_path($fileName))) {
                return back()->with('error', 'No data found for export.');
            }

            return redirect($fileName);
        }

    }
}
