<?php

namespace App\Http\Controllers\WHM;

use App\Exceptions\ApiGenericException;
use App\Helpers\CommonHelper;
use App\Helpers\ConstantHelper;
use App\Helpers\Helper;
use App\Helpers\Inventory\StockReservation;
use App\Helpers\StoragePointHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\WHM\PicklistItemResource;
use App\Http\Resources\WHM\PicklistResource;
use App\Lib\Services\WHM\MaterialIssueWhmJob;
use App\Models\ErpMaterialIssueHeader;
use App\Models\ErpMiItem;
use App\Lib\Services\WHM\PickingJob;
use App\Models\ErpPlHeader;
use App\Models\ErpPlItem;
use App\Models\StockLedgerReservation;
use App\Models\WHM\ErpItemUniqueCode;
use App\Models\WHM\ErpWhmJob;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Lib\Validation\WHM\PickingRequest as Validator;
use DB;

class PicklistTaskController extends Controller
{
    public function index(Request $request){
        $search = $request->input('search');
        $location = $request->input('store_id');
        $subLocation = $request->input('sub_store_id');
        $jobs = ErpWhmJob::with(['store' => function($q){
                        $q->select('id','store_name');
                    },'subStore' => function($q){
                        $q->select('id','name','is_warehouse_required');
                    }, 'morphable' => function ($q) {
                        $q->with('pickingItems');
                    }])
                    ->where('type', CommonHelper::PICKING)
                    ->when($search, function ($query) use ($search) {
                        $query->whereHasMorph('morphable', ['App\Models\ErpPlHeader', 'App\Models\ErpMaterialIssueHeader'], function ($q) use ($search) {
                             $q->where(function($q2) use ($search) {
                                $q2->where('document_number', 'like', "%{$search}%")
                                    ->orWhere('book_code', 'like', "%{$search}%");
                            });
                        });
                    })
                    ->when($location, function ($query) use ($location) {
                        $query->where('store_id', $location);
                    })
                    ->when($subLocation, function ($query) use ($subLocation) {
                        $query->where('sub_store_id', $subLocation);
                    })
                    ->whereIn('status',[CommonHelper::PENDING,CommonHelper::IN_PROGRESS, CommonHelper::DEVIATION])
                    ->orderBy('id','desc')
                    ->paginate(CommonHelper::PAGE_LENGTH_10);

        $jobResources = PicklistResource::collection($jobs->getCollection());

        return [
            'message' => 'Records fetched successfully',
            "data" => [
                'records' => $jobResources,
                'pagination' => [
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
                    'from' => $jobs->firstItem(),
                    'to' => $jobs->lastItem(),
                ],
            ],
        ];
    }

    public function items(Request $request){
        $validator = (new Validator($request))->getItems();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $job = (new PickingJob())->getJob($request->job_id);
        $storeId = $request->store_id;        
        $items = $this->getItemDetailsData($job, $storeId);
        return [
            'message' => 'Records fetched successfully',
            "data" => $items,
        ];

    }

    public function itemDetail(Request $request){
        $validator = (new Validator($request))->getItemDetail();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $storeId = $request->store_id;
        $job = (new PickingJob())->getJob($request->job_id);
        
        if ($job -> trns_type === ConstantHelper::MATERIAL_ISSUE_SERVICE_ALIAS_NAME) {
            $plItem = ErpMiItem::whereHas('header', function($q) use($storeId){
                    $q->where('from_store_id',$storeId);
                })
                ->where('id', $request->pl_item_id)
                ->with('attributes')
                ->select('id','material_issue_id AS pl_header_id','item_id','item_name','item_code',
                    // DB::raw('CAST(inventory_uom_qty AS UNSIGNED) as quanity')
                    DB::raw("(
                        CAST(inventory_uom_qty AS UNSIGNED) * 
                        (
                            SELECT IFNULL(storage_uom_count, 1)
                            FROM erp_items 
                            WHERE erp_items.id = erp_mi_items.item_id
                        )
                    ) as quantity")
                )
                ->first();
        } else if ($job -> trns_type === ConstantHelper::PL_SERVICE_ALIAS) {
            $plItem = ErpPlItem::whereHas('header', function($q) use($storeId){
                    $q->where('store_id',$storeId);
                })
                ->where('id', $request->pl_item_id)
                ->select('id','pl_header_id','item_id','item_name','item_code',
                    DB::raw("(
                        CAST(inventory_uom_qty AS UNSIGNED) * 
                        (
                            SELECT IFNULL(storage_uom_count, 1)
                            FROM erp_items 
                            WHERE erp_items.id = erp_pl_items.item_id
                        )
                    ) as quantity"),
                    'attributes'
                )
                ->first();
        }

        if (!$plItem) {
            throw ValidationException::withMessages([
                'pl_item_id' => ['Item not found.'],
            ]);
        }

        $plScannedItemUids = ErpItemUniqueCode::where('job_id',$request->job_id)->pluck('uid')->toArray(); 
        
        $plItemId = $plItem->id;
 
        if($plItem){
            $reservedStock = $plItem->stockReservation()
                ->where('issue_book_type',$job -> trns_type)
                ->where('issue_header_id',$plItem->pl_header_id);

            $transType = $reservedStock->pluck('receipt_book_type')
                ->unique()
                ->toArray();

            $mrnIds = $reservedStock->pluck('receipt_detail_id')
                ->toArray();

            $itemId = $plItem->item_id;
            
            // STEP 1: Fetch quantities grouped by storage_point_id
            $storageData = ErpItemUniqueCode::where('item_id', $itemId)
                ->where('store_id', $storeId)
                ->whereIn('trns_type', $transType)
                ->where('doc_type', CommonHelper::RECEIPT)
                ->where(function($q) use($plScannedItemUids){
                    $q->whereIn('utilized_id',$plScannedItemUids)
                    ->orWhereNull('utilized_id');
                })
                ->whereIn('morphable_id',$mrnIds)
                ->select('storage_point_id', DB::raw('COUNT(*) as quantity'))
                ->groupBy('storage_point_id')
                ->get();

            // STEP 2: Map storage point detail with quantity
            $plItem->storage_points = $storageData->map(function ($record) use($storeId, $itemId, $plItemId){
                $detailsResponse = StoragePointHelper::getStoragePointDetailById($record->storage_point_id);
                $scannedPackets = self::scannedPackets($storeId, $itemId, $record->storage_point_id, $plItemId);

                return [
                    'quantity' => $record->quantity,
                    'details' => $detailsResponse['data'] ?? null,
                    'scannedPacketsCount' => $scannedPackets ? $scannedPackets->count() : null,
                    'scannedPackets' => $scannedPackets ?? null,
                ];
            });

        } else {
            $plItem->storage_points = null;
            $plItem->scannedPacketsCount = 0;
            $plItem->scannedPackets = null;
        }

        return [
            'data' => new PicklistItemResource($plItem),
            'message' => "Record fetched successfully.",
        ];

    }

    private function scannedPackets($storeId, $itemId, $storagePointId, $plItemId){
        $scannedPacketsUids = ErpItemUniqueCode::where('item_id', $itemId)
            ->where('store_id', $storeId)
            ->where('morphable_type', 'App\Models\ErpPlItem')
            ->where('morphable_id', $plItemId)
            ->where('doc_type', CommonHelper::RECEIPT)
            ->where('status',CommonHelper::SCANNED)
            ->get()
            ->pluck('uid')
            ->toArray();

        // Fetch the original MRN packets and their storage_point_id
        $packets = ErpItemUniqueCode::whereIn('utilized_id', $scannedPacketsUids)
            ->where('storage_point_id', $storagePointId)
            ->select('uid','item_uid', 'storage_point_id')
            ->get();


        return $packets;
    }

    public function saveAsDraft(Request $request){
        $validator = (new Validator($request))->scanQr();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $pickingJob = new PickingJob();
        $job = $pickingJob->getJob($request->job_id);
        $detail = $pickingJob->getPlItemDetail($job->trns_type, $request->pl_item_id);

        $subStore = $job->subStore;
        if ($subStore && $subStore->is_warehouse_required) {
            if (!$request->filled('storage_point_id')) {
                throw ValidationException::withMessages([
                    'storage_point_id' => ['Storage point is required.'],
                ]);
            }
        }

        $storagePointId = $request->storage_point_id ?? NULL;
        $reservedStock = $pickingJob->reservedStock($job, $detail->id);
        $transType = $reservedStock['transType'];
        $mrnIds = $reservedStock['mrnIds'];
        
        $validPackets = $pickingJob->getMrnPackets($request->packet_ids, $storagePointId, $detail->item_id, $mrnIds, $transType);
        $invalidPackets = array_diff($request->packet_ids, $validPackets);
        if (!empty($invalidPackets)) {
            return [
                "message" => 'Invalid or mismatched packet IDs: ' . implode(', ', $invalidPackets),
                "status" => false
            ];
        }

        // custom validation after
        $scannedPackets = $pickingJob->getScannedPackets($request->job_id,$request->packet_ids, $request->pl_item_id);
        $alreadyScanned = $scannedPackets['data']->pluck('item_uid')->toArray();
        if (!empty($alreadyScanned)) {
            throw ValidationException::withMessages([
                'packet_ids' => ['Some packets are already scanned: ' . implode(', ', $alreadyScanned)],
            ]);
        }

        $scannedPacketsRes = $pickingJob->getScannedPackets($request->job_id, NULL , $request->pl_item_id);
        $scannedPacketsUid = $scannedPacketsRes['data']->pluck('uid')->toArray();

        // Validate stock
        $packetData = ErpItemUniqueCode::when($storagePointId, function ($query) use ($storagePointId) {
                $query->where('storage_point_id', $storagePointId);
            })
            ->where(function($q) use($request,$scannedPacketsUid){
                $q->where(function($q1) use($request){
                    $q1->whereIn('item_uid', $request->packet_ids)
                    ->whereNull('utilized_id');
                })->orWhereIn('utilized_id',$scannedPacketsUid);
            })
            ->where('item_id',$detail->item_id)
            ->whereIn('morphable_id',$mrnIds)
            ->whereIn('trns_type', $transType)
            ->get();

        $count = $packetData->count();
        $noOfPackets = optional($detail->item)->storage_uom_count ?? 1;
        $inventoryQty = (int) $detail->inventory_uom_qty;

        $qty = $count/$noOfPackets;
        $stockRes = StockReservation::validateReservedStock($job->trns_type,$job->morphable_id,$request->pl_item_id,$qty);
        if($stockRes['status'] == 'error'){
            throw ValidationException::withMessages([
                'pl_item_id' => [$stockRes['message']],
            ]);
        }

        // Validate qr scan packet wise
        foreach ($packetData->groupBy('packet_no') as $packetNo => $qrs) {
            if ($qrs->count() > $inventoryQty) {
                throw ValidationException::withMessages([
                    'packet_data.' . $packetNo => "You can only scan $inventoryQty quantity per packet. Already scanned: " . $qrs->count(),
                ]);
            }
        }

        \DB::beginTransaction();
        try {
            // Get Login User
            $user = Helper::getAuthenticatedUser();
            
            // Update Job Status
            if($job->status != CommonHelper::DEVIATION){
                $job->status = CommonHelper::IN_PROGRESS;
                $job->save();
            }

            $header = $job->morphable;
            $pickingJob->scanQRCodes($detail, $header, $job, $request->packet_ids, $storagePointId, $user, CommonHelper::PICKING, $transType);

            if ($request->storage_point_id) {
                $scannedPackets = $pickingJob->getScannedPackets($request->job_id,$request->packet_ids, $request->pl_item_id);

                $totalIncomingWeight = 0;
                $totalIncomingVolume = 0;
                foreach($scannedPackets['data'] as $packet){
                    // Calculate packet weight & volume
                    $itemWeight = StoragePointHelper::getItemWeight($packet->item_id, $packet->packet_no);
                    if($itemWeight['status'] == "error"){
                        throw ValidationException::withMessages([
                            'packets' => $itemWeight['message'],
                        ]);
                    }

                    $packetWeight = $itemWeight['data']['packetWeight'];
                    $packetVolume = $itemWeight['data']['packetVolume'];

                    $totalIncomingWeight += $packetWeight;
                    $totalIncomingVolume += $packetVolume;
                }

                StoragePointHelper::updateStorageWeight($request->storage_point_id, $totalIncomingWeight, $totalIncomingVolume);
            }

            \DB::commit();
            return [
                'message' => 'Task saved in draft'
            ];
        } catch (\Exception $e) {
            \DB::rollback();
            throw new ApiGenericException($e->getMessage());
        }
    }


    public function updateStatus(Request $request){
        $validator = (new Validator($request))->updateStatus();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // custom validation after
        $pickingJob = new PickingJob();
        $job = $pickingJob->getJob($request->job_id);

        $subStore = $job->subStore;
        if ($subStore && $subStore->is_warehouse_required) {
            if (!$request->filled('storage_point_id')) {
                throw ValidationException::withMessages([
                    'storage_point_id' => ['Storage point is required.'],
                ]);
            }
        }

        $morphableType = "";
        if ($job -> trns_type === ConstantHelper::MATERIAL_ISSUE_SERVICE_ALIAS_NAME) {
            $morphableType = "App\Models\ErpMiItem";
        } else if ($job -> trns_type === ConstantHelper::PL_SERVICE_ALIAS) {
            $morphableType = "App\Models\ErpPlItem";
        }

        $uniqueCode = ErpItemUniqueCode::where('item_uid', $request->packet_id)
                        ->where('job_id',$request->job_id)
                        ->where('morphable_type', $morphableType)
                        ->where('status',CommonHelper::SCANNED)
                        ->first();
                        
        if (!$uniqueCode) {
            throw ValidationException::withMessages([
                'packet_id' => ['Packet ID not found.'],
            ]);
        }

        if ($job->status == CommonHelper::DEVIATION) {
            throw ValidationException::withMessages([
                'job_id' => ['The job status is deviation.'],
            ]);
        }

        \DB::beginTransaction();
        try {
            $reservedStock = StockLedgerReservation::where('issue_book_type',$job->trns_type)
                ->where('issue_header_id',$job->morphable_id)
                ->where('issue_detail_id',$uniqueCode->morphable_id);

            $transType = $reservedStock->pluck('receipt_book_type')
                ->toArray();

            $mrnIds = $reservedStock->pluck('receipt_detail_id')
                ->toArray();

            $storagePointId = $request->storage_point_id ?? NULL;
            $mrnDetail = ErpItemUniqueCode::where('item_uid', $request->packet_id)
                // ->where('storage_point_id',$request->storage_point_id)
                ->when($storagePointId, function ($query) use ($storagePointId) {
                    $query->where('storage_point_id', $storagePointId);
                })
                ->whereIn('trns_type', $transType)
                ->whereIn('morphable_id', $mrnIds)
                ->where('status',CommonHelper::SCANNED)
                ->where('utilized_id',$uniqueCode->uid)
                ->first();

            if($mrnDetail){
                $mrnDetail->utilized_id = NULL;
                $mrnDetail->save();
                $uniqueCode->delete();
            }

            if($mrnDetail->storage_point_id){
                $itemWeight = StoragePointHelper::getItemWeight($mrnDetail->item_id, $mrnDetail->packet_no);
                if($itemWeight['status'] == "error"){
                    throw ValidationException::withMessages([
                        'packet_id' => $itemWeight['message'],
                    ]);
                }

                
                StoragePointHelper::addStorageWeight($mrnDetail->storage_point_id, $itemWeight['data']['packetWeight'], $itemWeight['data']['packetVolume']);
            }

            \DB::commit();
            return [
                'data' => $request->packet_id,
                'message' => 'Packet deleted successfully.'
            ];
        } catch (\Exception $e) {
            \DB::rollback();
            throw new ApiGenericException($e->getMessage());
        }

    }

    public function closeJob(Request $request){
        $validator = (new Validator($request))->closeJob();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // custom validation after
        $pickingJob = new PickingJob();
        $job = $pickingJob->getJob($request->job_id);

        // Check if job is already closed with deviation=0 and incoming deviation=0
        if ($job->job_closed_at !== null ) {
            if ($job->deviation_qty == $request->deviation) {
                throw ValidationException::withMessages([
                    'job_id' => ['Job already closed.'],
                ]);
            }
        }

        \DB::beginTransaction();
        try {

            $job->status = CommonHelper::CLOSED;
            $job->job_closed_at = now();
            $job->deviation_qty = $request->deviation;
            $message = 'Job closed successfully.';

            // Update status based on deviation
            if($request->deviation > 0){
                $job->status = CommonHelper::DEVIATION;
                $message = 'Job closed with deviation '.$request->deviation.'.';
            }

            $job->save();

            $actionType = $job->status == CommonHelper::DEVIATION ? CommonHelper::DEVIATION : CommonHelper::getJobType($job->morphable_type) .' completed';
            $header = $job->morphable;
            $bookId = $header->book_id;
            $docId = $header->id;
            $revisionNumber = $header->revision_number ?? 0;
            $modelName = $job->morphable_type;
            $remarks = NULL;
            CommonHelper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $actionType, $modelName);

            if ($job -> trns_type === ConstantHelper::MATERIAL_ISSUE_SERVICE_ALIAS_NAME) {
                $mi = ErpMaterialIssueHeader::find($job -> morphable_id);
                if ($mi && $job -> status === CommonHelper::CLOSED) {
                    //Check Recieve job
                    if ($mi -> to_sub_store ?-> is_warehouse_required) {
                        //Only Issue and Recieve Job
                        (new MaterialIssueWhmJob)->createJob($mi->id,'App\Models\ErpMaterialIssueHeader', CommonHelper::PUTAWAY);
                        foreach ($mi->items as $miItem) {
                            $status = StockReservation::settlementOfReservedStocks(ConstantHelper::MATERIAL_ISSUE_SERVICE_ALIAS_NAME, $mi->id, $miItem->id, $miItem->inventory_uom_qty, false);
                            if ($status['status'] == 'error') {
                                throw new ApiGenericException($status['message']);
                            }
                        }
                    } else {
                        //Direct Issue and Recieve
                        foreach ($mi->items as $miItem) {
                            $status = StockReservation::settlementOfReservedStocks(ConstantHelper::MATERIAL_ISSUE_SERVICE_ALIAS_NAME, $mi->id, $miItem->id, $miItem->inventory_uom_qty, true);
                            if ($status['status'] == 'error') {
                                throw new ApiGenericException($status['message']);
                            }
                        }
                        $subStoreId = $header->to_sub_store_id ?? NULL;
                        $storeId = $header->to_store_id ?? NULL;
                        (new PickingJob())->generateQRCodes($subStoreId,$job,$storeId);
                    }
                }
            } else if ($job -> trns_type === ConstantHelper::PL_SERVICE_ALIAS) {
                $pickList = ErpPlHeader::find($job->morphable_id);
                if($pickList && $job -> status == CommonHelper::CLOSED){
                    foreach ($pickList->inv_items as $plItem) {
                        $status = StockReservation::settlementOfReservedStocks(ConstantHelper::PL_SERVICE_ALIAS, $pickList->id, $plItem->id, $plItem->inventory_uom_qty, true);
                        if ($status['status'] == 'error') {
                            throw new ApiGenericException($status['message']);
                        }
                    }
                }
                $subStoreId = $header->staging_sub_store_id ?? NULL;
                (new PickingJob())->generateQRCodes($subStoreId,$job,$header->store_id);
            }

            \DB::commit();
            return [
                'message' => $message
            ];
        } catch (\Exception $e) {
            \DB::rollback();
            throw new ApiGenericException($e->getMessage());
        }
    }

    public function pendingTasks(Request $request){
        $validator = (new Validator($request))->pendingJob();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $pickingJob = new PickingJob();
        $job = $pickingJob->getJob($request->job_id);

        $status = $request->status;
        $storagePointId = $request->storage_point_id;
        $reservedStock = $pickingJob->reservedStock($job,$request->pl_item_id);
        $transType = $reservedStock['transType'];
        $mrnIds = $reservedStock['mrnIds'];

        $scannedPacketsUids = ErpItemUniqueCode::where('job_id', $request->job_id)
                ->where('morphable_id',$request->pl_item_id)
                ->where('job_type',CommonHelper::PICKING)
                ->get()
                ->pluck('uid')
                ->toArray();

        $pendingTasksQuery = ErpItemUniqueCode::with(['storagePoint' => function($q){
                $q->select('id', 'storage_number');
            },'vendor' => function ($q) {
                $q->select('id', 'vendor_code', 'company_name');
            }])
            ->whereIn('morphable_id',$mrnIds)
            ->whereIn('trns_type',$transType)
            // ->whereNull('utilized_id')
            ->when($storagePointId,function($q) use($storagePointId){
                $q->where('storage_point_id',$storagePointId);
            });

            if($status == CommonHelper::PENDING){
                $pendingTasksQuery->whereNull('utilized_id');
            }elseif($status == CommonHelper::SCANNED){
                $pendingTasksQuery->whereIn('utilized_id',$scannedPacketsUids);
            }else{
                $pendingTasksQuery->where(function($q) use($scannedPacketsUids){
                    $q->whereNull('utilized_id')
                    ->orWhereIn('utilized_id',$scannedPacketsUids);
                });
            }

        $pendingTasks = $pendingTasksQuery->select('uid','job_id','group_id','company_id','organization_id','book_code','doc_no','doc_date','status','item_id','item_uid','item_name','item_code','item_attributes','status','utilized_id','storage_point_id','packet_no','total_packets','vendor_id')
            ->get();

        return [
            'message' => 'Records fetched successfully',
            "data" => $pendingTasks,
        ];

    }

    public function scanStorage(Request $request){
        $validator = (new Validator($request))->scanStorage();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $pickingJob = new PickingJob(); 
        $job = $pickingJob->getJob($request->job_id);
        $detail = $pickingJob->getPlItemDetail($job->trns_type, $request->pl_item_id);

        $storageNumber = $request->input('storage_number');
        $response = StoragePointHelper::getStoragePointDetail($storageNumber);

        if($response['code'] == 500){
            throw ValidationException::withMessages([
                'storage_number' => [$response['message']],
            ]);
        }

        if (empty($response['data'])) {
            throw ValidationException::withMessages([
                'storage_number' => ['Storage point data not found.'],
            ]);
        }

        $storagePoint = $response['data'];
        $storagePointId = $storagePoint->id;

        // Get reserved stock
        $reservedStock = $pickingJob->reservedStock($job,$request->pl_item_id);
        $transType = $reservedStock['transType'];
        $mrnIds = $reservedStock['mrnIds'];
        
        $packets = ErpItemUniqueCode::where('storage_point_id',$storagePointId)
            ->where('item_id',$detail->item_id)
            ->whereNull('utilized_id')
            ->whereIn('morphable_id',$mrnIds)
            ->whereIn('trns_type', $transType)
            ->pluck('item_uid')
            ->toArray();

        if (empty($packets)) {
            throw ValidationException::withMessages([
                'packet_ids' => ['No packets found in storage point: ' . $request->storage_number],
            ]);
        }

        return [
            'data' => $response['data'],
            'message' => $response['message'],
        ];

    }

    private function getItemDetailsData(ErpWhmJob $job, int $storeId)
    {
        $morphableId = $job -> morphable_id;
        $trnsType = $job -> trns_type;
        if ($trnsType === ConstantHelper::PL_SERVICE_ALIAS) {
            return ErpPlItem::where('pl_header_id', $morphableId)
                ->whereHas('header', function($q) use($storeId){
                    $q->where('store_id',$storeId);
                })
                ->select(
                    'id as pl_item_id',
                    'pl_header_id',
                    'item_id',
                    'item_name',
                    'item_code',
                    // DB::raw('CAST(inventory_uom_qty AS UNSIGNED) as quanity'),
                    DB::raw("(
                        CAST(inventory_uom_qty AS UNSIGNED) * 
                        (
                            SELECT IFNULL(storage_uom_count, 1)
                            FROM erp_items 
                            WHERE erp_items.id = erp_pl_items.item_id
                        )
                    ) as quantity"),
                    'attributes',
                    DB::raw("(
                        SELECT COUNT(*)
                        FROM erp_item_unique_codes
                        WHERE morphable_id = erp_pl_items.id
                        AND morphable_type = '" . addslashes(ErpPlItem::class) . "'
                        AND status = '" . CommonHelper::SCANNED . "'
                    ) as scanned_count"),
                )->paginate(CommonHelper::PAGE_LENGTH_10);
        } else if ($trnsType === ConstantHelper::MATERIAL_ISSUE_SERVICE_ALIAS_NAME) {
            return ErpMiItem::where('material_issue_id', $morphableId)
                ->whereHas('header', function($q) use($storeId){
                    $q->where('from_store_id',$storeId);
                })
                ->with('attributes')
                ->select(
                    'id as pl_item_id',
                    'material_issue_id AS pl_header_id',
                    'item_id',
                    'item_name',
                    'item_code',
                    // DB::raw('CAST(inventory_uom_qty AS UNSIGNED) as quanity'),
                    DB::raw("(
                        CAST(inventory_uom_qty AS UNSIGNED) * 
                        (
                            SELECT IFNULL(storage_uom_count, 1)
                            FROM erp_items 
                            WHERE erp_items.id = erp_mi_items.item_id
                        )
                    ) as quantity"),
                    DB::raw("(
                        SELECT COUNT(*)
                        FROM erp_item_unique_codes
                        WHERE morphable_id = erp_mi_items.id
                        AND morphable_type = '" . addslashes(ErpMiItem::class) . "'
                        AND status = '" . CommonHelper::SCANNED . "'
                    ) as scanned_count")
                )->paginate(CommonHelper::PAGE_LENGTH_10);
        } else {
            return [];
        }
    }

    public function scannedItemQrs(Request $request){
        $validator = (new Validator($request))->scannedItemQrs();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        \DB::beginTransaction();
        try {
            $scannedPackets = ErpItemUniqueCode::where('job_type', CommonHelper::PICKING)
                ->where('morphable_id', $request->pl_item_id)
                ->where('job_id',$request->job_id)
                ->where('status',CommonHelper::SCANNED)
                ->where('doc_type',CommonHelper::ISSUE)
                ->select('uid','job_id','group_id','company_id','organization_id','book_code','doc_no','doc_date','status','item_id','item_uid','item_name','item_code','item_attributes','status','vendor_id','storage_point_id')
                ->get();


        \DB::commit();
            return [
                'data' => $scannedPackets
            ];
        } catch (\Exception $e) {
            \DB::rollback();
            throw new ApiGenericException($e->getMessage());
        }
    }
}
