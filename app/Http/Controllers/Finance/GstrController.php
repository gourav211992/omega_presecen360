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
use App\Helpers\GstrHelper;
use App\Helpers\Helper;

class GstrController extends Controller
{

    public function index(Request $request){
        $pageLengths = ConstantHelper::PAGE_LENGTHS;
        $length = $request->length ? $request->length : ConstantHelper::PAGE_LENGTH_10;

        $startDate = Carbon::now()->startOfMonth(); // Start of the current month
        $endDate = Carbon::now()->endOfMonth(); 

        // Check if there's an applied date filter
        if ($request->has('date_range') && $request->date_range != '') {
            $dates = explode(' to ', $request->date_range);
            $startDate = $dates[0] ? Carbon::parse($dates[0])->startOfDay() : null;
            $endDate = isset($dates[1]) ? Carbon::parse($dates[1])->startOfDay():  Carbon::parse($dates[0])->startOfDay();
        }

        $connection = config('database.connections.mysql.database');

        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $organizationGstin = $organization->gst_number;

        $masterData = self::masterData();

        $types = ErpGstInvoiceType::select('erp_gst_invoice_types.id', 'erp_gst_invoice_types.name')
            ->where(function($q) use($request){
                if($request->search){
                    $q->where('erp_gst_invoice_types.name', 'like', '%' . $request->search . '%');
                }
            })
            ->paginate($length);

        $invoiceData = [];
        foreach($types as $type){
            $invoiceData[$type->name] = $this->getSummaryData($request, $type->id, $type->name, $organizationGstin, $startDate, $endDate);

        }

        return view('finance.gstr.index',[
            'pageLengths' => $pageLengths,
            'invoiceData' => $invoiceData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'groups' => $masterData['groups'],
            'organizationData' => $masterData['organizations'],
            'companies' => $masterData['companies'],
            'types' => $types,
            'invoiceTypes' => $masterData['types'],
        ]);
    }

    private function getSummaryData($request, $typeId, $type, $organizationGstin, $startDate, $endDate){
        switch ($type) {
            case 'b2b':
                return self::getB2bSummary($request, $typeId, $organizationGstin, $startDate, $endDate);
            case 'hsn':
                return self::getHsnSummary($request, $typeId, $organizationGstin, $startDate, $endDate);
            default:
                return self::getInvoiceData($request, $typeId, $organizationGstin, $startDate, $endDate);
        }
    }

    private function getB2bSummary($request, $typeId, $gstin, $startDate, $endDate){
        $data = GstrCompiledData::where(function ($query) use ($request) {
                    $this->filter($request,$query);
                })
                ->where('invoice_type_id',$typeId)
                ->whereBetween("erp_gstr_compiled_data.invoice_date", [$startDate, $endDate])
                ->where("erp_gstr_compiled_data.supplier_gstin", $gstin)
                ->select(
                    DB::raw("erp_gstr_compiled_data.taxable_amt as taxable_amt"),
                    DB::raw("erp_gstr_compiled_data.rate as gst_rate"),
                    DB::raw("erp_gstr_compiled_data.sgst as sgst"),
                    DB::raw("erp_gstr_compiled_data.cgst as cgst"),
                    DB::raw("erp_gstr_compiled_data.igst as igst"),
                    DB::raw("erp_gstr_compiled_data.cess as cess"),
                    DB::raw("erp_gstr_compiled_data.invoice_amt as invoice_amt")
                )
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

    private function getHsnSummary($request, $typeId, $gstin, $startDate, $endDate){
        $data = GstrCompiledData::where(function ($query) use ($request) {
                    $this->filter($request,$query);
                })
                ->where('invoice_type_id',$typeId)
                ->whereBetween("erp_gstr_compiled_data.invoice_date", [$startDate, $endDate])
                ->where("erp_gstr_compiled_data.supplier_gstin", $gstin)
                ->select(
                    DB::raw("erp_gstr_compiled_data.taxable_amt as taxable_amt"),
                    DB::raw("erp_gstr_compiled_data.rate as gst_rate"),
                    DB::raw("erp_gstr_compiled_data.sgst as sgst"),
                    DB::raw("erp_gstr_compiled_data.cgst as cgst"),
                    DB::raw("erp_gstr_compiled_data.igst as igst"),
                    DB::raw("erp_gstr_compiled_data.cess as cess"),
                    DB::raw("erp_gstr_compiled_data.invoice_amt as invoice_amt")
                )
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

    private function getInvoiceData($request, $typeId, $gstin, $startDate, $endDate){
        $data = GstrCompiledData::where(function ($query) use ($request) {
                    $this->filter($request,$query);
                })
                ->where('invoice_type_id',$typeId)
                ->whereBetween("erp_gstr_compiled_data.invoice_date", [$startDate, $endDate])
                ->where("erp_gstr_compiled_data.supplier_gstin", $gstin)
                ->select(
                    DB::raw("erp_gstr_compiled_data.taxable_amt as taxable_amt"),
                    DB::raw("erp_gstr_compiled_data.rate as gst_rate"),
                    DB::raw("erp_gstr_compiled_data.sgst as sgst"),
                    DB::raw("erp_gstr_compiled_data.cgst as cgst"),
                    DB::raw("erp_gstr_compiled_data.igst as igst"),
                    DB::raw("erp_gstr_compiled_data.cess as cess"),
                    DB::raw("erp_gstr_compiled_data.invoice_amt as invoice_amt")
                )
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
        $gstrInvoiceTypes = ErpGstInvoiceType::on('mysql_master')->where(function($q) use($request){
            if($request->search){
                $q->where('name', 'like', '%' . $request->search . '%');
            }
        })->get();

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

        $startDate = Carbon::now()->startOfMonth(); // Start of the current month
        $endDate = Carbon::now()->endOfMonth(); 

        // Check if there's an applied date filter
        if ($request->has('date_range') && $request->date_range != '') {
            $dates = explode(' to ', $request->date_range);
            $startDate = $dates[0] ? Carbon::parse($dates[0])->startOfDay() : null;
            $endDate = isset($dates[1]) ? Carbon::parse($dates[1])->startOfDay():  Carbon::parse($dates[0])->startOfDay();
        }

        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $supplierGstin = $organization->gst_number;
        
        $type = ErpGstInvoiceType::on('mysql_master')->where('id',$id)->first();

        $gstrData = GstrCompiledData::where(function($query) use($request){
                            $this->filter($request,$query);
                    
                            if($request->has('search')){
                                $query->where('erp_gstr_compiled_data.party_name', 'like', '%' . $request->search . '%')
                                    ->orWhere('erp_gstr_compiled_data.party_gstin', 'like', '%' . $request->search . '%');
                            }

            })
        ->whereBetween('erp_gstr_compiled_data.invoice_date', [$startDate, $endDate])
        ->where('invoice_type_id',$id)
        ->where('supplier_gstin', $supplierGstin)
        ->whereNotNull('erp_gstr_compiled_data.invoice_id')
        ->groupBy('erp_gstr_compiled_data.invoice_id')
        ->paginate($length);

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

    private function filter($request,$query){
        if($request->group_id){
            $query->where('erp_gstr_compiled_data.group_id', 'like', '%' . $request->group_id . '%');
        }
        
        if($request->company_id){
            $query->where('erp_gstr_compiled_data.company_id', 'like', '%' . $request->company_id . '%');
        }

        if($request->organization_id){
            $query->where('erp_gstr_compiled_data.organization_id', 'like', '%' . $request->organization_id . '%');
        }

        return $query;

    }

    private function masterData(){
        $groups = OrganizationGroup::select('id','name')->get();
        $organizations = Organization::select('id','name')->get();
        $companies = OrganizationCompany::select('id','name')->get();
        $types = ErpGstInvoiceType::on('mysql_master')->get();

        return [
            'groups' => $groups,
            'organizations' => $organizations,
            'companies' => $companies,
            'types' => $types
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
            $types = ErpGstInvoiceType::on('mysql_master')->where(function($q) use($request){
                        if($request->search){
                            $q->where('erp_gst_invoice_types.name', 'like', '%' . $request->search . '%');
                        }
                    })->get();

            $zipFileName = "temp/finance/gstr1/{$gstin}_all_csvs.zip";
            $zipPath = public_path($zipFileName);
            $zip = new \ZipArchive;

            if (file_exists($zipPath)) {
                unlink($zipPath); // remove old file if exists
            }

            if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                foreach ($types as $type) {
                    $fileName = "temp/finance/gstr1/{$type->name}.csv";
                    $gstrExport->export($fileName, $request, $type->id, $type->name, $gstin);
                    $zip->addFile(public_path($fileName), $type->name . '.csv');
                }

                $zip->close();

                return response()->download($zipPath);
            } else {
                return back()->with('error', 'Could not create ZIP file');
            }

        }else{    
            $type = ErpGstInvoiceType::on('mysql_master')->where('id',$id)->first();
            $fileName = "temp/finance/gstr1/".$gstin.'_'.$type->name.".csv";
            $gstrExport->export($fileName, $request, $id, $type->name, $gstin);
            return redirect($fileName);
        }

    }
}