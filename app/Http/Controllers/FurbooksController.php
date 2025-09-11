<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ErpLedgerFurbook;
use App\Models\ErpStagingFurbooksLedger;
use App\Models\Ledger;
use App\Helpers\Helper;
use App\Imports\FurbooksImport;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use App\Mail\ImportComplete;
use App\Models\ErpCurrency;
use App\Helpers\ConstantHelper;
use App\Helpers\InventoryHelper;
use Exception;
use DB;
use App\Models\Voucher;
use App\Models\ItemDetail;
use Carbon\Carbon;
use App\Helpers\CurrencyHelper;
use App\Http\Controllers\BookController;
use App\Models\Currency;
use App\Models\Organization;
use App\Models\OrganizationService;

class FurbooksController extends Controller
{
    

    public function store(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'book_id' => 'required|integer',
                'ledgers' => 'required|array|min:1',
                'ledgers.*.ledger_id' => 'required|integer',
                'ledgers.*.ledger_group_id' => 'required|integer',
                'ledgers.*.book_id' => 'required|integer',
                'ledgers.*.furbook_code' => 'required|string|max:100',
            ]);

            $user = Helper::getAuthenticatedUser();
            $organization = $user->organization;
            
            // Get policy data from service (following standard pattern)
            $parentUrl = 'furbooks'; // or appropriate service alias
            $services = Helper::getAccessibleServicesFromMenuAlias($parentUrl);
            
           if ($services && isset($services['services']) && !empty($services['services'])) {
                $firstService = $services['services']->first();
                $serviceId = $firstService->service_id;
                $policyData = Helper::getPolicyByServiceId($serviceId);
                
                if ($policyData && isset($policyData['policyLevelData'])) {
                    $policyLevelData = $policyData['policyLevelData'];
                    $groupId = $policyLevelData['group_id'];
                    $companyId = $policyLevelData['company_id'];
                    $organizationId = $policyLevelData['organization_id'];
                } else {
                    $groupId = $organization->group_id;
                    $companyId = $organization->company_id;
                    $organizationId = $user->organization_id;
                }
            } else {
                $groupId = $organization->group_id;
                $companyId = $organization->company_id;
                $organizationId = $user->organization_id;
            }

            // Validate unique furbook codes per ledger
            $ledgerCodes = [];
            foreach ($request->ledgers as $ledger) {
                $key = $ledger['ledger_id'] . '_' . $ledger['furbook_code'];
                if (in_array($key, $ledgerCodes)) {
                    return response()->json([
                        'message' => 'Duplicate furbook code found for the same ledger!'
                    ], 422);
                }
                $ledgerCodes[] = $key;
            }

            // Check if furbook record already exists for this book and organization
            $existingFurbook = ErpLedgerFurbook::where('book_id', $request->book_id)
                ->where('organization_id', $organizationId)
                ->first();

            if ($existingFurbook) {
                // Update existing record by merging new ledgers with existing ones
                $existingLedgers = json_decode($existingFurbook->ledgers, true) ?? [];
                
                // Merge new ledgers with existing ones (avoid duplicates)
                foreach ($request->ledgers as $newLedger) {
                    $exists = false;
                    foreach ($existingLedgers as $existingLedger) {
                        if ($existingLedger['ledger_id'] == $newLedger['ledger_id'] && 
                            $existingLedger['furbook_code'] == $newLedger['furbook_code']) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $existingLedgers[] = $newLedger;
                    }
                }
                
                // Update the existing record
                $existingFurbook->update([
                    'ledgers' => json_encode($existingLedgers),
                    'updated_at' => now()
                ]);
                
                $furbook = $existingFurbook;
            } else {
                // Create new furbook record with JSON ledgers data
                $furbook = ErpLedgerFurbook::create([
                    'book_id' => $request->book_id,
                    'group_id' => $groupId,
                    'company_id' => $companyId,
                    'organization_id' => $organizationId,
                    'status' => 'active',
                    'ledgers' => json_encode($request->ledgers), // Store ledgers as JSON
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Furbook data saved successfully!',
                'data' => $furbook
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while saving: ' . $e->getMessage()
            ], 500);
        }
    }

     public function index()
    {
        
        $parentUrl = 'vouchers';
        $serviceAlias = Helper::getAccessibleServicesFromMenuAlias($parentUrl);
        $bookTypes = $serviceAlias['services'];
        $bookTypes = collect($bookTypes)
            ->whereIn('alias', [
                ConstantHelper::JOURNAL_VOUCHER,
            ])
            ->unique('alias')  
            ->values() ?? [];
        
         

        // $bookTypes = collect($bookTypes)->whereIn('alias', [ConstantHelper::CONTRA_VOUCHER,ConstantHelper::JOURNAL_VOUCHER,ConstantHelper::OPENING_BALANCE])->values()??[];
       


        

        // $lastVoucher = Voucher::where('organization_id', Helper::getAuthenticatedUser()->organization_id)->orderBy('id', 'desc')->select('book_type_id', 'book_id')->first();
        // $currencies = Currency::where('status', ConstantHelper::ACTIVE)->select('id', 'name', 'short_name')->get();
        // $orgCurrency = Organization::where('id', Helper::getAuthenticatedUser()->organization_id)->value('currency_id');
        $allledgers = Ledger::get();
        // $allowedCVGroups = Helper::getChildLedgerGroupsByNameArray(ConstantHelper::CV_ALLOWED_GROUPS,'names');
        // $exlucdeJVGroups = Helper::getChildLedgerGroupsByNameArray(ConstantHelper::JV_EXCLUDE_GROUPS,'names');
        // $cost_centers = Helper::getActiveCostCenters();
       
        // pass authenticate user's org locations
     $locations = InventoryHelper::getAccessibleLocations();

    // Fetch saved furbook data
    $user = Helper::getAuthenticatedUser();
    $savedFurbooks = ErpLedgerFurbook::where('organization_id', $user->organization_id)
        ->orderBy('created_at', 'desc')
        ->get();

    // Debug: Log the raw data
    \Log::info('Saved Furbooks Count: ' . $savedFurbooks->count());
    \Log::info('User Organization ID: ' . $user->organization_id);

    // Process the saved furbook data
    $processedFurbooks = [];
    foreach ($savedFurbooks as $furbook) {
        $ledgers = json_decode($furbook->ledgers, true) ?? [];
        \Log::info('Processing furbook ID: ' . $furbook->id . ', Ledgers JSON: ' . $furbook->ledgers);
        
        foreach ($ledgers as $ledgerData) {
            $ledger = Ledger::find($ledgerData['ledger_id']);
            $ledgerGroup = \App\Models\Group::find($ledgerData['ledger_group_id']);
            
            $processedFurbooks[] = [
                'id' => $furbook->id,
                'book_id' => $furbook->book_id,
                'book_name' => $furbook->book->book_name ?? 'N/A',
                'book_code' => $furbook->book->book_code ?? 'N/A',
                'ledger_id' => $ledgerData['ledger_id'],
                'ledger_name' => $ledger->name ?? 'N/A',
                'ledger_group_id' => $ledgerData['ledger_group_id'],
                'ledger_group_name' => $ledgerGroup->name ?? 'N/A',
                'furbook_code' => $ledgerData['furbook_code'],
                'status' => $furbook->status,
                'created_at' => $furbook->created_at,
            ];
        }
    }

    \Log::info('Processed Furbooks Count: ' . count($processedFurbooks));

    return view('furbooks.index', compact('bookTypes','allledgers','locations','processedFurbooks'));
    }

    public function furbook_ledgers_search(Request $r)
    {
        $book = $r->series;
        $book = OrganizationService::where('id', $book)->first();
        $book = $book?->alias;

        if ($book == ConstantHelper::JOURNAL_VOUCHER) {
            $excludeNames = ConstantHelper::JV_EXCLUDE_GROUPS;
            $allChildIds = Helper::getChildLedgerGroupsByNameArray($excludeNames);

            $data = Ledger::where('status', 1)
                ->where('name', 'like', '%' . $r->keyword . '%')
                    // Exclude plain integer match
                ->whereNotIn('ledger_group_id', $allChildIds)
                    // Exclude JSON array match
                ->where(function ($query) use ($allChildIds) {
                    $i = 0;
                    $count = count($allChildIds);

                    while ($i < $count) {
                        $child = (string)$allChildIds[$i];

                        $query->whereJsonDoesntContain('ledger_group_id', $child);
                        $i++;
                    }
                });
        }

        else {
            $data = Ledger::where('status', 1)
                ->where('name', 'like', '%' . $r->keyword . '%');
        }

        $data = $data->select('id as value', 'name as label', 'cost_center_id')->get()->toArray();
        return response()->json($data);
    }

    public function destroy($id)
    {
        try {
            $user = Helper::getAuthenticatedUser();
            
            // Find the furbook record
            $furbook = ErpLedgerFurbook::where('id', $id)
                ->where('organization_id', $user->organization_id)
                ->first();
            
            if (!$furbook) {
                return response()->json([
                    'message' => 'Furbook record not found or you do not have permission to delete it.'
                ], 404);
            }
            
            // Delete the record
            $furbook->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Furbook record deleted successfully!'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting: ' . $e->getMessage()
            ], 500);
        }
    }


    //Api to insert the data in voucher table

    public function insertVocher(Request $request)
    {
        $numberPatternData = Helper::generateDocumentNumberNew($request->book_id, $request->date);

        if (!isset($numberPatternData)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid Book",
            ], 422);
        }

        $voucherExists = Voucher::where('voucher_no', $numberPatternData['document_number'])
            ->where('book_id', $request->book_id)
            ->exists();

        if ($voucherExists) {
            return response()->json([
                'success' => false,
                'message' => $request->voucher_no . ' Voucher No. Already Exist!',
            ], 409); // conflict
        }

        $validator = Validator::make($request->all(), [
            'voucher_name' => 'required|string',
            'date' => 'required|date',
            'document' => 'nullable|array',
            'document.*' => 'file',
            'debit_amt' => 'required|array',
            'credit_amt' => 'required|array',
            'voucher_no' => 'required|string',
            'ledger_id' => 'required|array',
            'ledger_id.*' => 'required|numeric|min:1',
            'parent_ledger_id' => 'required|array',
            'parent_ledger_id.*' => 'required|numeric|min:1',
            'location' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $organization = Helper::getAuthenticatedUser()->organization;

            // Create Voucher
            $voucher = new Voucher();
            $voucher->voucher_no = $numberPatternData['document_number'];
            $voucher->voucher_name = $request->voucher_name;
            $voucher->book_type_id = $request->book_type_id;
            $voucher->book_id = $request->book_id;

            // Currency-related fields
            $voucher->currency_id = $request->currency_id;
            $voucher->currency_code = $request->currency_code;
            $voucher->org_currency_exg_rate = $request->orgExchangeRate;
            $voucher->org_currency_id = $request->org_currency_id;
            $voucher->org_currency_code = $request->org_currency_code;
            $voucher->org_currency_exg_rate = $request->org_currency_exg_rate;
            $voucher->comp_currency_id = $request->comp_currency_id;
            $voucher->comp_currency_code = $request->comp_currency_code;
            $voucher->comp_currency_exg_rate = $request->comp_currency_exg_rate;
            $voucher->group_currency_id = $request->group_currency_id;
            $voucher->group_currency_code = $request->group_currency_code;
            $voucher->group_currency_exg_rate = $request->group_currency_exg_rate;

            $voucher->date = $request->date;
            $voucher->remarks = $request->remarks;
            $voucher->amount = $request->amount;
            $voucher->organization_id = $organization->id;
            $voucher->group_id = $organization->group_id;
            $voucher->company_id = $organization->company_id;
            $voucher->revision_number = 0;

            $voucher->document_date = $request->date;
            $voucher->location = $request->location;
            $voucher->doc_no = $numberPatternData['doc_no'];
            $voucher->doc_number_type = $numberPatternData['type'];
            $voucher->doc_reset_pattern = $numberPatternData['reset_pattern'];
            $voucher->doc_prefix = $numberPatternData['prefix'];
            $voucher->doc_suffix = $numberPatternData['suffix'];
            $voucher->approvalStatus = $request->status;
            $voucher->created_by = Helper::getAuthenticatedUser()->auth_user_id;
            $voucher->approvalLevel = 1;

            // Upload files
            if ($request->hasFile('document')) {
                $files = $request->file('document');
                $fileNames = [];
                foreach ($files as $file) {
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $destinationPath = public_path('voucherDocuments');
                    $file->move($destinationPath, $fileName);
                    $fileNames[] = $fileName;
                }
                $voucher->document = json_encode($fileNames);
            }

            $userData = Helper::userCheck();
            $voucher->voucherable_id = Helper::getAuthenticatedUser()->auth_user_id;
            $voucher->voucherable_type = $userData['user_type'];

            $voucher->save();

            // Approval logic
            if ($request->status == ConstantHelper::SUBMITTED) {
                $approveDocument = Helper::approveDocument(
                    $voucher->book_id,
                    $voucher->id,
                    $voucher->revision_number ?? 0,
                    $voucher->remarks,
                    $request->file('attachment'),
                    $voucher->approval_level,
                    'submit',
                    $voucher->amount ?? 0,
                    get_class($voucher)
                );
                $voucher->approvalStatus = $approveDocument['approvalStatus'] ?? $voucher->document_status;
                $voucher->save();
            }

            // Item details
            foreach ($request->debit_amt as $index => $debitAmount) {
                if (isset($request->ledger_id[$index]) && isset($request->parent_ledger_id[$index])) {
                    $notename = "notes" . ($index + 1);

                    ItemDetail::create([
                        'voucher_id' => $voucher->id,
                        'ledger_id' => $request->ledger_id[$index],
                        'debit_amt' => $request->debit_amt[$index] ?? 0,
                        'credit_amt' => $request->credit_amt[$index] ?? 0,
                        'debit_amt_org' => $request->org_debit_amt[$index] ?? 0,
                        'credit_amt_org' => $request->org_credit_amt[$index] ?? 0,
                        'debit_amt_comp' => $request->comp_debit_amt[$index] ?? 0,
                        'credit_amt_comp' => $request->comp_credit_amt[$index] ?? 0,
                        'debit_amt_group' => $request->group_debit_amt[$index] ?? 0,
                        'credit_amt_group' => $request->group_credit_amt[$index] ?? 0,
                        'ledger_parent_id' => $request->parent_ledger_id[$index],
                        'cost_center_id' => $request->cost_center_id[$index] ?? null,
                        'notes' => $request->$notename,
                        'date' => $request->date,
                        'organization_id' => $organization->id,
                        'group_id' => $organization->group_id,
                        'company_id' => $organization->company_id, // fixed (was group_id before)
                        'remarks' => $request->item_remarks[$index] ?? "",
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Voucher created successfully.',
                'data' => $voucher
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showImportForm()
    {
        // $urlSegmentAlias = 'vochers';
        // $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($urlSegmentAlias);
        // if (count($servicesBooks['services']) == 0) {
        //     return redirect()->route('/');
        // }
        return view('furbooks.import');
    }


    public function import(Request $request)
    {
        $user = Helper::getAuthenticatedUser();

        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls|max:30720',
            ]);
            
            if (!$request->hasFile('file')) {
                return response()->json([
                    'status' => false,
                    'message' => 'No file uploaded.',
                ], 400);
            }

            $file = $request->file('file');

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'The uploaded file format is incorrect or corrupted. Please upload a valid Excel file.',
                ], 400);
            }

            $sheet = $spreadsheet->getActiveSheet();
            $rowCount = $sheet->getHighestRow() - 1;
            if ($rowCount > 10000) {
                return response()->json([
                    'status' => false,
                    'message' => 'The uploaded file contains more than 10000 items. Please upload a file with 10000 or fewer items.',
                ], 400);
            }
            if ($rowCount < 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'The uploaded file is empty.',
                ], 400);
            }

            // Clear existing staging data for this user
            ErpStagingFurbooksLedger::where('organization_id', $user->organization_id)->delete();

            $import = new FurbooksImport();
            Excel::import($import, $file);

            $successfulFurbooks = $import->getSuccessfulFurbooks();
            $failedFurbooks = $import->getFailedFurbooks();
            
            $mailData = [
                'modelName' => 'Furbooks',
                'successful_items' => $successfulFurbooks,
                'failed_items' => $failedFurbooks,
                'export_successful_url' => route('furbooks.export.successful'),
                'export_failed_url' => route('furbooks.export.failed'),
            ];
            
            if (count($failedFurbooks) > 0) {
                $message = 'Furbooks import completed with some failures. Some records were not imported.';
                $status = 'failure';
            } else {
                $message = 'Furbooks import completed successfully.';
                $status = 'success';
            }
            
            // Send email notification if user has email
            if ($user->email) {
                try {
                    Mail::to($user->email)->send(new ImportComplete($mailData));
                } catch (Exception $e) {
                    $message .= " However, there was an error sending the email notification.";
                }
            }
            
            return response()->json([
                'status' => $status,
                'message' => $message,
                'successful_customers' => $successfulFurbooks,
                'failed_customers' => $failedFurbooks,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid file format or file size. Please upload a valid .xlsx or .xls file with a maximum size of 30MB.',
            ], 400);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to import furbooks: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function exportSuccessful()
    {
        $user = Helper::getAuthenticatedUser();
        
        $successfulRecords = ErpStagingFurbooksLedger::where('organization_id', $user->organization_id)
            ->where('status', 'Success')
            ->get();

        $fileName = 'furbooks_successful_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new class($successfulRecords) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            private $records;
            
            public function __construct($records) {
                $this->records = $records;
            }
            
            public function collection() {
                return $this->records->map(function($record) {
                    return [
                        'Location ID' => $record->location_id,
                        'Currency Code' => $record->currency_code,
                        'Furbooks Code' => $record->furbooks_code,
                        'Cost Center' => $record->cost_center,
                        'Remark' => $record->remark,
                        'Final Remark' => $record->final_remark,
                        'Document Date' => $record->document_date,
                        'Debit Amount' => $record->debit_amount,
                        'Credit Amount' => $record->credit_amount,
                        'Amount' => $record->amount,
                        'Status' => $record->status,
                        'Created At' => $record->created_at,
                    ];
                });
            }
            
            public function headings(): array {
                return [
                    'Location ID',
                    'Currency Code', 
                    'Furbooks Code',
                    'Cost Center',
                    'Remark',
                    'Final Remark',
                    'Document Date',
                    'Debit Amount',
                    'Credit Amount',
                    'Amount',
                    'Status',
                    'Created At'
                ];
            }
        }, $fileName);
    }

    public function exportFailed()
    {
        $user = Helper::getAuthenticatedUser();
        
        $failedRecords = ErpStagingFurbooksLedger::where('organization_id', $user->organization_id)
            ->where('status', 'Failed')
            ->get();

        $fileName = 'furbooks_failed_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new class($failedRecords) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            private $records;
            
            public function __construct($records) {
                $this->records = $records;
            }
            
            public function collection() {
                return $this->records->map(function($record) {
                    return [
                        'Location ID' => $record->location_id,
                        'Currency Code' => $record->currency_code,
                        'Furbooks Code' => $record->furbooks_code,
                        'Cost Center' => $record->cost_center,
                        'Remark' => $record->remark,
                        'Final Remark' => $record->final_remark,
                        'Document Date' => $record->document_date,
                        'Debit Amount' => $record->debit_amount,
                        'Credit Amount' => $record->credit_amount,
                        'Amount' => $record->amount,
                        'Status' => $record->status,
                        'Created At' => $record->created_at,
                    ];
                });
            }
            
            public function headings(): array {
                return [
                    'Location ID',
                    'Currency Code', 
                    'Furbooks Code',
                    'Cost Center',
                    'Remark',
                    'Final Remark',
                    'Document Date',
                    'Debit Amount',
                    'Credit Amount',
                    'Amount',
                    'Status',
                    'Created At'
                ];
            }
        }, $fileName);
    }

    /**
     * Get series data for dropdown population
     */
    public function getSeries(Request $request)
    {
        try {
            $user = Helper::getAuthenticatedUser();
            $organization = $user->organization;

            // Get books/series data for the organization
            $series = \App\Models\Book::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->select('id', 'book_code', 'book_name')
                ->orderBy('book_code')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $series
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch series data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfer furbooks data from staging table to vouchers table
     * Similar to voucher store functionality with auto-populated fields
     */
    public function transferToVoucher()
    {
        // try {
        //     DB::beginTransaction();    //Open the try and cath once debug process complete

          
            $staging_furbooks = ErpStagingFurbooksLedger::get();
            foreach ($staging_furbooks as $ids) {
                // Get all furbooks and search for matching furbook_code
                $matchedData = null;
                $furbooks = ErpLedgerFurbook::all();
                
                foreach ($furbooks as $furbook) {
                    if ($furbook && $furbook->ledgers) {
                        $ledgers = json_decode($furbook->ledgers, true);
                        
                        
                        $matched = collect($ledgers)->firstWhere('furbook_code', $ids->furbooks_code);
                        
                        if ($matched) {
                            $matchedData = $matched;
                            break; 
                        }
                    }
                }
                if ($matchedData) {
                    $bookId        = $matchedData['book_id'] ?? null;
                    $ledgerId      = $matchedData['ledger_id'] ?? null;
                    $ledgerGroupId = $matchedData['ledger_group_id'] ?? null;
                    $furbookCode   = $matchedData['furbook_code'] ?? null;
                    
                    // Get organization ID from staging record
                    $organizationId = $ids->organization_id ?? null;
                    
                } else {
                    // Log when no match is found
                    \Log::warning('No matching furbook_code found:', [
                        'searching_for' => $ids->furbook_code
                    ]);
                    continue;
                }
            }  
            $user = Helper::getAuthenticatedUser();
            $organization = $organizationId;

            //Need to fatch the organization details from the organization_id
          
            // Get document date (use first record's date or current date)
            $documentDate = $staging_ids->document_date ?? Carbon::now()->format('Y-m-d');
            // Generate document number using BookController method (same as frontend)
            $bookController = new BookController();
            $docRequest = new Request([
                'book_id' => '135',
                'document_date' => $documentDate
            ]);
            
            $docResponse = $bookController->getBookDocNoAndParameters($docRequest);
            $docData = $docResponse->getData();
            if ($docData->status !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => $docData->message ?? 'Unable to generate document number'
                ], 422);
            }
            $numberPatternData = $docData->data->doc;
            // Get currency details from staging records or use organization's default
             $currencyCode = $staging_ids->currency_code ?? 'INR';
             // Get currency ID from erp_currency table using currency_code
             $currency = ErpCurrency::where('short_name', $currencyCode)->first();
             if (!$currency) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Currency not found: ' . $currencyCode
                 ], 422);
             }
 
             // Get exchange rates using CurrencyHelper with current date and currency_code
             $exchangeRateData = CurrencyHelper::getCurrencyExchangeRates($currency->id, $documentDate);
             $exchangeRates = $exchangeRateData['data'];
             
           
            $voucherExists = Voucher::where('voucher_no', $numberPatternData->document_number)
                ->where('book_id', $request->book_id)
                ->exists();

            if ($voucherExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Voucher number already exists: ' . $numberPatternData->document_number
                ], 422);
            }

            // Get location_id from first staging record or use accessible locations
            $locationId = $staging_ids->location_id;
            $services = Helper::getAccessibleServicesFromMenuAlias('vouchers');
            if ($services && isset($services['services']) && !empty($services['services'])) {
                $firstService = $services['services']->first();
                $serviceId = $firstService->service_id;
                $policyData = Helper::getPolicyByServiceId($serviceId);
                
                
                if ($policyData && isset($policyData['policyLevelData'])) {
                    $policyLevelData = $policyData['policyLevelData'];
                    $groupId = $policyLevelData['group_id'];
                    $companyId = $policyLevelData['company_id'];
                    $organizationId = $policyLevelData['organization_id'];
                } else {
                    $groupId = $organization->group_id;
                    $companyId = $organization->company_id;
                }
            } else {
                $groupId = $organization->group_id;
                $companyId = $organization->company_id;
                
            }

            $organizationId = $staging_ids->organization_id;

           
            
            // Create voucher record (following voucher store pattern)
            $voucher = new Voucher();
            $voucher->voucher_no = $numberPatternData->document_number;
            $voucher->voucher_name = $request->voucher_name;
            $voucher->book_id = $request->book_id;
            $voucher->currency_id = $currency->id;
            $voucher->currency_code = $currency->code;
            
            if($exchangeRates){
                $voucher->org_currency_id = $exchangeRates['organization_currency_id'];
                $voucher->org_currency_code = $exchangeRates['organization_currency_code'];
                $voucher->org_currency_exg_rate = $exchangeRates['organization_exchange_rate'];
                $voucher->comp_currency_id = $exchangeRates['company_currency_id'];
                $voucher->comp_currency_code = $exchangeRates['company_currency_code'];
                
                $voucher->comp_currency_exg_rate = $exchangeRates['company_exchange_rate'];
                $voucher->group_currency_id = $exchangeRates['group_currency_id'];
                $voucher->group_currency_code = $exchangeRates['group_currency_code'];
                $voucher->group_currency_exg_rate = $exchangeRates['group_exchange_rate'];
            }
            
            // Date and amount
            $voucher->date = $documentDate;
            $voucher->document_date = $documentDate;
            $voucher->amount = $totalAmount;
            $voucher->remarks = $request->remarks;
            
            // Common fields
            $voucher->organization_id = $organizationId;
            $voucher->group_id = $groupId;
            $voucher->company_id = $companyId;
            $voucher->location = $locationId;
            $voucher->revision_number = 0;
            
            // Document number fields
            $voucher->doc_no = $numberPatternData->doc_no;
            $voucher->doc_number_type = $numberPatternData->type;
            $voucher->doc_reset_pattern = $numberPatternData->reset_pattern;
            $voucher->doc_prefix = $numberPatternData->prefix;
            $voucher->doc_suffix = $numberPatternData->suffix;
            
            // Approval fields
            $voucher->approvalStatus = ConstantHelper::PENDING;
            $voucher->approvalLevel = 1;
            $voucher->created_by = $user->auth_user_id;
            
            // User fields
            $userData = Helper::userCheck();
            $voucher->voucherable_id = $user->auth_user_id;
            $voucher->voucherable_type = $userData['user_type'];

            $voucher->save();

        
            // Update staging records status to 'Transferred'
            ErpStagingFurbooksLedger::whereIn('id', $request->staging_ids)
                ->update(['status' => 'Transferred', 'updated_at' => now()]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Furbooks data transferred to voucher successfully',
                'data' => [
                    'voucher_id' => $voucher->id,
                    'voucher_no' => $voucher->voucher_no,
                    'document_date' => $voucher->document_date,
                    'amount' => $voucher->amount,
                    'records_transferred' => $stagingRecords->count()
                ]
            ]);

        // } catch (\Illuminate\Validation\ValidationException $e) {
        //     DB::rollBack();
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Validation failed',
        //         'errors' => $e->errors()
        //     ], 422);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Failed to transfer furbooks data: ' . $e->getMessage()
        //     ], 500);
        // }
    }

}
