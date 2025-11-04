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
use Barryvdh\DomPDF\Facade\Pdf;
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

    //Section 3.1
    private function getGstr3bSection3_1Data($gstin, $month)
    {
        $baseQuery = GstrCompiledData::query()
            ->where('erp_gstr_compiled_data.supplier_gstin', $gstin)
            ->whereNotNull('invoice_id');

    
        $b2bTypeIds = ErpGstInvoiceType::whereIn('code', ['b2b', 'b2ba'])
            ->where('status', ConstantHelper::ACTIVE)
            ->pluck('id')
            ->toArray();
        
        

        $b2bData = (clone $baseQuery)
            ->whereIn('invoice_type_id', $b2bTypeIds)
            ->selectRaw('
                SUM(taxable_amt) as taxable_amt,
                SUM(igst) as igst,
                SUM(cgst) as cgst,
                SUM(sgst) as sgst,
                SUM(cess) as cess
            ')
            ->first();

    
        $zeroRatedData = (clone $baseQuery)
            ->where('rate', 0)
            ->selectRaw('SUM(taxable_amt) as taxable_amt')
            ->first();

    
        $nilExemptedData = (clone $baseQuery)
            ->selectRaw('
                SUM(expt_amt + nil_amt) as taxable_amt
            ')
            ->first();

    
        $reverseChargeData = (clone $baseQuery)
            ->where('reverse_charge', 'Y')
            ->selectRaw('
                SUM(taxable_amt) as taxable_amt,
                SUM(igst) as igst,
                SUM(cgst) as cgst,
                SUM(sgst) as sgst,
                SUM(cess) as cess
            ')
            ->first();

       

    
        $nonGstData = (clone $baseQuery)
            ->where('non_gst_amt', '>', 0)
            ->selectRaw('SUM(non_gst_amt) as non_gst_amt')
            ->first();

    
        return [
            'b2b' => [
                'taxable_amt' => $b2bData->taxable_amt ?? 0,
                'igst' => $b2bData->igst ?? 0,
                'cgst' => $b2bData->cgst ?? 0,
                'sgst' => $b2bData->sgst ?? 0,
                'cess' => $b2bData->cess ?? 0,
            ],
            'zero_rated' => [
                'taxable_amt' => $zeroRatedData->taxable_amt ?? 0,
                'igst' => 0,
                'cgst' => 0,
                'sgst' => 0,
                'cess' => 0,
            ],
            'nil_exempted' => [
                'taxable_amt' => $nilExemptedData->taxable_amt ?? 0,
                'igst' => 0,
                'cgst' => 0,
                'sgst' => 0,
                'cess' => 0,
            ],
            'reverse_charge' => [
                'taxable_amt' => $reverseChargeData->taxable_amt ?? 0,
                'igst' => $reverseChargeData->igst ?? 0,
                'cgst' => $reverseChargeData->cgst ?? 0,
                'sgst' => $reverseChargeData->sgst ?? 0,
                'cess' => $reverseChargeData->cess ?? 0,
            ],
            'non_gst' => [
                'taxable_amt' => $nonGstData->non_gst_amt ?? 0,
                'igst' => 0,
                'cgst' => 0,
                'sgst' => 0,
                'cess' => 0,
            ],
        ];
    }

    private function getGstr3bSection3_2Data($gstin, $month)
    {
        $results = [
            'unregistered' => ['taxable_value' => 0, 'igst' => 0, 'details' => []],
            'composition'  => ['taxable_value' => 0, 'igst' => 0, 'details' => []],
            'uin'          => ['taxable_value' => 0, 'igst' => 0, 'details' => []],
        ];

     
        $unregistered = GstrCompiledData::
            // where('erp_gstr_compiled_data.month', $month)
            // ->where('erp_gstr_compiled_data.supplier_gstin', $gstin)
            where('erp_gstr_compiled_data.igst', '>', 0)
            ->where(function ($q) {
                $q->whereNull('erp_gstr_compiled_data.party_gstin')
                ->orWhere('erp_gstr_compiled_data.party_gstin', '');
            })
            ->whereNotNull('erp_gstr_compiled_data.place_of_supply')
            ->where('doc_type', 'b2b')
            ->where('invoice_type_id', ErpGstInvoiceType::where('code', 'b2b')->pluck('id')->toArray())
            ->select(
                DB::raw("'unregistered' as category"),
                'erp_gstr_compiled_data.place_of_supply',
                DB::raw('SUM(erp_gstr_compiled_data.taxable_amt) as taxable_value'),
                DB::raw('SUM(erp_gstr_compiled_data.igst) as igst')
            )
            ->groupBy('erp_gstr_compiled_data.place_of_supply')
            ->get();
        

        $results['unregistered']['taxable_value'] = $unregistered->sum('taxable_value');
        $results['unregistered']['igst'] = $unregistered->sum('igst');
        $results['unregistered']['details'] = $unregistered->toArray();


      
        $composition = GstrCompiledData::where('erp_gstr_compiled_data.igst', '>', 0)
            ->where('erp_gstr_compiled_data.supplier_gstin', $gstin)
            // ->where('month', $month)
            // ->whereIn('doc_type', ['b2cl','b2cla'])
            // ->whereIn('invoice_type_id', ErpGstInvoiceType::whereIn('code', ['b2cl','b2cla'])->pluck('id')->toArray())
            ->whereNotNull('erp_gstr_compiled_data.party_gstin')
            ->whereNotNull('erp_gstr_compiled_data.place_of_supply')
            ->select(
                DB::raw("'composition' as category"),
                'erp_gstr_compiled_data.place_of_supply',
                DB::raw('SUM(erp_gstr_compiled_data.taxable_amt) as taxable_value'),
                DB::raw('SUM(erp_gstr_compiled_data.igst) as igst')
            )
            ->groupBy('erp_gstr_compiled_data.place_of_supply')
            ->get();
        
        
       

        $results['composition']['taxable_value'] = $composition->sum('taxable_value');
        $results['composition']['igst'] = $composition->sum('igst');
        $results['composition']['details'] = $composition->toArray();


        
        $uin = GstrCompiledData::where('erp_gstr_compiled_data.month', $month)
            ->where('erp_gstr_compiled_data.supplier_gstin', $gstin)
            ->where('erp_gstr_compiled_data.igst', '>', 0)
            ->whereNotNull('erp_gstr_compiled_data.party_gstin')
            ->where('erp_gstr_compiled_data.ur_type', 'uin') // <- actual field for UIN
            ->whereNotNull('erp_gstr_compiled_data.place_of_supply')
            ->select(
                DB::raw("'uin' as category"),
                'erp_gstr_compiled_data.place_of_supply',
                DB::raw('SUM(erp_gstr_compiled_data.taxable_amt) as taxable_value'),
                DB::raw('SUM(erp_gstr_compiled_data.igst) as igst')
            )
            ->groupBy('erp_gstr_compiled_data.place_of_supply')
            ->get();

        $results['uin']['taxable_value'] = $uin->sum('taxable_value');
        $results['uin']['igst'] = $uin->sum('igst');
        $results['uin']['details'] = $uin->toArray();


        return $results;
    }

    
    //Section 4 
    private function getGstr4Section($month, $gstin)
    {
       
        $getTaxData = function ($headerTable, $detailTable, $tedTable, $headerKey, $filters = []) {
            $query = DB::table("$headerTable as mh")
                ->join("$detailTable as md", "mh.id", '=', "md.$headerKey")
                ->join("$tedTable as mea", "mh.id", '=', "mea.$headerKey")
                ->where('mh.document_status', '!=', 'draft')
                ->whereNull('mh.deleted_at')
                ->whereNull('md.deleted_at')
                ->whereNull('mea.deleted_at');

           
            foreach ($filters as $key => $value) {
                $query->where($key, $value);
            }

            // Group and sum by TED code type (IGST, CGST, SGST, CESS)
            $result = $query
                ->selectRaw("
                    SUM(CASE WHEN mea.ted_code LIKE '%IGST%' THEN mea.ted_amount ELSE 0 END) as igst,
                    SUM(CASE WHEN mea.ted_code LIKE '%CGST%' THEN mea.ted_amount ELSE 0 END) as cgst,
                    SUM(CASE WHEN mea.ted_code LIKE '%SGST%' THEN mea.ted_amount ELSE 0 END) as sgst,
                    SUM(CASE WHEN mea.ted_code LIKE '%CESS%' THEN mea.ted_amount ELSE 0 END) as cess
                ")
                ->first();

            return [
                'igst' => $result->igst ?? 0,
                'cgst' => $result->cgst ?? 0,
                'sgst' => $result->sgst ?? 0,
                'cess' => $result->cess ?? 0,
            ];
        };

        // Import of Goods
        $importTotals = $getTaxData(
            'erp_mrn_headers',
            'erp_mrn_details',
            'erp_mrn_extra_amounts',
            'mrn_header_id'
        );

        // Total taxable amount (optional, retained from your version)
        $taxableAmount = DB::table('erp_mrn_headers as mh')
            ->join('erp_mrn_details as md', 'mh.id', '=', 'md.mrn_header_id')
            ->whereMonth('mh.document_date', $month)
            ->whereYear('mh.document_date', date('Y'))
            ->where('mh.document_status', '!=', 'draft')
            ->whereNull('mh.deleted_at')
            ->whereNull('md.deleted_at')
            ->sum('md.taxable_amount');

        // Import of Services
        $importSerTotals = $getTaxData(
            'erp_expense_headers',
            'erp_expense_details',
            'erp_expense_ted',
            'expense_header_id'
        );

        return [
            'import_goods' => $importTotals,
            'import_services' => $importSerTotals,
        ];
    }

    //Section 5 - Values of exempt, nil-rated and non-GST inward supplies
    private function getGstr3bSection5Data($gstin, $month)
    {
        $getTaxExemptData = function ($headerTable, $detailTable, $tedTable, $headerKey) use ($month, $gstin) {
            $query = DB::table("$headerTable as h")
                ->join("$detailTable as d", "h.id", '=', "d.$headerKey")
                ->leftJoin("$tedTable as t", "h.id", '=', "t.$headerKey")
                ->whereMonth('h.document_date', $month)
                ->whereYear('h.document_date', date('Y'))
                ->where('h.document_status', '!=', 'draft')
                ->whereNull('h.deleted_at')
                ->whereNull('d.deleted_at');

          
            $result = $query
                ->selectRaw("
                    SUM(CASE 
                        WHEN t.ted_code IS NULL OR t.ted_amount = 0 OR t.ted_type != 'Tax' 
                        THEN d.taxable_amount 
                        ELSE 0 
                    END) as exempt_nil_amount,
                    SUM(CASE 
                        WHEN d.taxable_amount > 0 AND (t.ted_code IS NULL OR t.ted_amount = 0)
                        THEN d.taxable_amount 
                        ELSE 0 
                    END) as non_gst_amount,
                    COUNT(DISTINCT h.id) as transaction_count
                ")
                ->first();

            return [
                'exempt_nil_amount' => $result->exempt_nil_amount ?? 0,
                'non_gst_amount' => $result->non_gst_amount ?? 0,
            ];
        };

        // Get data from MRN tables
        $mrnData = $getTaxExemptData(
            'erp_mrn_headers',
            'erp_mrn_details',
            'erp_mrn_extra_amounts',
            'mrn_header_id'
        );

        // Get data from PB tables
        $pbData = $getTaxExemptData(
            'erp_pb_headers',
            'erp_pb_details',
            'erp_pb_ted',
            'header_id'
        );

        // Combine MRN and PB data - simple totals without state-wise split
        $combinedData = [
            'composition_exempt_nil' => [
                'total_amount' => ($mrnData['exempt_nil_amount'] ?? 0) + ($pbData['exempt_nil_amount'] ?? 0),
            ],
            'non_gst' => [
                'total_amount' => ($mrnData['non_gst_amount'] ?? 0) + ($pbData['non_gst_amount'] ?? 0),
            ],
        ];

        return $combinedData;
    }


    public function gstr3b(Request $request)
    {
        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $gstin = $organization->gst_number;
        $organizationName = $organization->name;


        $fyear = Helper::getFinancialYear(date("Y-m-d"));
        $financialYear = $fyear["range"] ?? "2025-26";


        $currentDate = now();
        $previousMonthDate = $currentDate->copy()->subMonth();
        $previousMonthName = $previousMonthDate->format("M");
        $previousMonthYear = $previousMonthDate->year;
        $previousMonth = $previousMonthName . "-" . $previousMonthYear;
        $month = $previousMonthDate->month;


        $gstr3bSection3_1Data = $this->getGstr3bSection3_1Data($gstin, $month);
        $gstr3bSection3_2Data = $this->getGstr3bSection3_2Data($gstin, $month);
        $gstr3bSection4Data = $this->getGstr4Section($month, $gstin);
        $gstr3bSection5Data = $this->getGstr3bSection5Data($gstin, $month);
        
      
   
        return view("finance.gstr.gstr3b", compact(
            "financialYear",
            "previousMonth",
            "fyear",
            "gstin",
            "organizationName",
            "gstr3bSection3_1Data",
            "gstr3bSection3_2Data",
            "gstr3bSection4Data",
            "gstr3bSection5Data"
        ));
    }

    public function gstr3bPdf(Request $request)
    {
        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $gstin = $organization->gst_number;
        $organizationName = $organization->name;

        $fyear = Helper::getFinancialYear(date("Y-m-d"));
        $financialYear = $fyear["range"] ?? "2025-26";

        $currentDate = now();
        $previousMonthDate = $currentDate->copy()->subMonth();
        $previousMonthName = $previousMonthDate->format("M");
        $previousMonthYear = $previousMonthDate->year;
        $previousMonth = $previousMonthName . "-" . $previousMonthYear;
        $month = $previousMonthDate->month;

        // Get all GSTR-3B section data using the same methods as main function
        $gstr3bSection3_1Data = $this->getGstr3bSection3_1Data($gstin, $month);
        $gstr3bSection3_2Data = $this->getGstr3bSection3_2Data($gstin, $month);
        $gstr3bSection4Data = $this->getGstr4Section($month, $gstin);
        $gstr3bSection5Data = $this->getGstr3bSection5Data($gstin, $month);

        $pdf = Pdf::loadView('finance.gstr.gstr3b-pdf', compact(
            "financialYear",
            "previousMonth",
            "fyear",
            "gstin",
            "organizationName",
            "gstr3bSection3_1Data",
            "gstr3bSection3_2Data",
            "gstr3bSection4Data",
            "gstr3bSection5Data"
        ));

        $pdf->setPaper('A4', 'portrait');
        
        $filename = "GSTR3B_" . $gstin . "_" . $previousMonth . ".pdf";
        
        return $pdf->download($filename);
    }
}
