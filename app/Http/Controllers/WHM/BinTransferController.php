<?php

namespace App\Http\Controllers\WHM;

use App\Exceptions\ApiGenericException;
use App\Helpers\CommonHelper;
use App\Helpers\Helper;
use App\Helpers\StoragePointHelper;
use App\Http\Controllers\Controller;
use App\Lib\Services\WHM\WhmJob;
use App\Models\WHM\ErpItemUniqueCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use DB;

class BinTransferController extends Controller
{
    public function index(Request $request){
        $validator = Validator::make($request->all(),[
            'storage_number' => ['required'],
        ],[
            'storage_number.required' => 'Storage number is required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $storeId = $request->input('store_id');
        $subStoreId = $request->input('sub_store_id');
        $storageNumber = $request->storage_number;

        $storagePointDetail = StoragePointHelper::getStoragePointDetail($storageNumber);
        if($storagePointDetail['status'] == "error"){
            throw ValidationException::withMessages([
                'storage_point_id' => $storagePointDetail['message'],
            ]);
        }

        $storageData = $storagePointDetail['data'];

        $items = ErpItemUniqueCode::where('storage_point_id',$storageData->id)
            ->where('doc_type',CommonHelper::RECEIPT)
            ->when($storeId, function ($query) use ($storeId) {
                $query->where('store_id', $storeId);
            })
            ->when($subStoreId, function ($query) use ($subStoreId) {
                $query->where('sub_store_id', $subStoreId);
            })
            ->whereNull('utilized_id')
            ->whereNotNull('storage_point_id')
            ->select('item_id','item_code','item_name','item_attributes',DB::raw('COUNT(*) as quantity'))
            ->groupBy('item_id')
            ->paginate(CommonHelper::PAGE_LENGTH_10);
        
            return [
                "data" => [
                    'storage_point' => $storageData,
                    'items' => $items
                ]
            ];
    }

    public function binTransfer(Request $request){
        $validator = Validator::make($request->all(),[
            'item_ids' => ['required', 'array', 'distinct'],
            'from_storage_number' => ['required'],
            'to_storage_number' => ['required','different:from_storage_number'],
        ],[
            'item_ids.required' => 'Item IDs are required',
            'item_ids.distinct' => 'Item IDs must be unique.',
            'from_storage_number.required' => 'Storage number id is required',
            'to_storage_number.required' => 'Storage number id is required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Get storage point details safely
        $fromStoragePoint = $this->validateStoragePoint($request->from_storage_number, 'from_storage_number');
        $toStoragePoint   = $this->validateStoragePoint($request->to_storage_number, 'to_storage_number');

        // Preload target capacity
        $currentWeight = $toStoragePoint->current_weight ?? 0;
        $maxWeight = $toStoragePoint->max_weight ?? null;
        $currentVolume = $toStoragePoint->current_volume ?? 0;
        $maxVolume = $toStoragePoint->max_volume ?? null;

        // Fetch all item weights & volumes in bulk
        $itemWeight = StoragePointHelper::getItemsWeight($request->item_ids);
        if($itemWeight['status'] == "error"){
            throw ValidationException::withMessages([
                'item_ids' => $itemWeight['message'],
            ]);
        }

        $unmappedItemIds = [];
        foreach ($request->item_ids as $itemId) {
            $mapped = StoragePointHelper::isStorageNumberMappedToItem(
                $itemId,
                $request->to_storage_number,
                $fromStoragePoint->store_id,
                $fromStoragePoint->sub_store_id
            );

            if (!$mapped) {
                $unmappedItemIds[] = $itemId;
            }
        }
      
        if (!empty($unmappedItemIds)) {
            throw ValidationException::withMessages([
                'to_storage_number' => "Storage point is not mapped to item IDs: " . implode(', ', $unmappedItemIds),
            ]);
        }

        // For each item, compute weights/volumes and validate mapping to to‐storage
        $totalIncomingWeight = 0;
        $totalIncomingVolume = 0;
        $invalidItems = [];
        $itemsData = [];

        $items = ErpItemUniqueCode::where('storage_point_id',$fromStoragePoint->id)
            ->where('doc_type',CommonHelper::RECEIPT)
            ->whereIn('item_id',$request->item_ids)
            ->whereNull('utilized_id')
            ->whereNotNull('storage_point_id')
            ->chunk(100, function($data) use (&$totalIncomingWeight, &$totalIncomingVolume, &$invalidItems, $itemWeight, &$itemsData) {
                if ($data->isEmpty()) {
                    throw ValidationException::withMessages([
                        'item_ids' => 'No valid items found for transfer.'
                    ]);
                }
                
                foreach ($data as $item) {
                    $itemsData[] = $item;
                    
                    // Check if item exists in weights array
                    if (!isset($itemWeight['data'][$item->item_id])) {
                        $invalidItems[] = $item->item_id;
                        continue;
                    }

                    // Check if packet exists
                    if (!isset($itemWeight['data'][$item->item_id][$item->packet_no])) {
                        $invalidItems[] = $item->item_id;
                        continue;
                    }

                    // calculate weights & volumes
                    $data = $itemWeight['data'][$item->item_id][$item->packet_no];

                    $packetWeight = $data['packetWeight'];
                    $packetVolume = $data['packetVolume'];

                    $totalIncomingWeight += $packetWeight;
                    $totalIncomingVolume += $packetVolume;
                }
                
            });
            
        // Throw error if invalid items found
        if (!empty($invalidItems)) {
            throw ValidationException::withMessages([
                'item_ids' => 'Invalid items: ' . implode(', ', $invalidItems)
            ]);
        }

        // Check if weight limits are exceeded
        if (!is_null($maxWeight) && ($currentWeight + $totalIncomingWeight) > $maxWeight) {
            throw ValidationException::withMessages([
                'to_storage_number' => ["Storage point weight limit exceeded. Max: {$maxWeight}, Current: {$currentWeight}, Incoming: {$totalIncomingWeight}"],
            ]);
        }

        // Check if volume limits are exceeded
        if (!is_null($maxVolume) && ($currentVolume + $totalIncomingVolume) > $maxVolume) {
            throw ValidationException::withMessages([
                'to_storage_number' => ["Storage point volume limit exceeded. Max: {$maxVolume}, Current: {$currentVolume}, Incoming: {$totalIncomingVolume}"],
            ]);
        }

        \DB::beginTransaction();
        try {
            $user = Helper::getAuthenticatedUser();
            (new WhmJob())->binTransfer($itemsData, $toStoragePoint->id, $user->id);

            $addResponse = StoragePointHelper::addStorageWeight($toStoragePoint->id, $totalIncomingWeight, $totalIncomingVolume);
            if($addResponse['status'] == "error"){
                throw ValidationException::withMessages([
                    'to_storage_number' => $addResponse['message'],
                ]);
            }

            $updateResponse = StoragePointHelper::updateStorageWeight($fromStoragePoint->id, $totalIncomingWeight, $totalIncomingVolume);
            if($updateResponse['status'] == "error"){
                throw ValidationException::withMessages([
                    'to_storage_number' => $updateResponse['message'],
                ]);
            }

            \DB::commit();
            return [
                'message' => 'Data transferred successfully.'
            ];
        } catch (\Exception $e) {
            \DB::rollback();
            throw new ApiGenericException($e->getMessage());
        }

    }

    private function validateStoragePoint($number, $key)
    {
        $detail = StoragePointHelper::getStoragePointDetail($number);
        if ($detail['status'] == 'error') {
            throw ValidationException::withMessages([
                $key => $detail['message'],
            ]);
        }
        return $detail['data'];
    }

    public function scanPackets(Request $request){
        $validator = Validator::make($request->all(),[
            'packet_ids' => ['required', 'array', 'distinct','max:50'],
            'from_storage_number' => ['required'],
            'to_storage_number' => ['required','different:from_storage_number'],
        ],[
            'packet_ids.required' => 'Packet IDs are required',
            'from_storage_number.required' => 'Storage point is required',
            'to_storage_number.required' => 'Storage point is required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Validate storage points
        $fromStoragePoint = $this->validateStoragePoint($request->from_storage_number, 'from_storage_number');
        $toStoragePoint   = $this->validateStoragePoint($request->to_storage_number, 'to_storage_number');

        // Preload storage point capacity
        $currentWeight = $toStoragePoint->current_weight ?? 0;
        $maxWeight = $toStoragePoint->max_weight ?? null;
        $currentVolume = $toStoragePoint->current_volume ?? 0;
        $maxVolume = $toStoragePoint->max_volume ?? null;

        // Fetch Items
        $items = ErpItemUniqueCode::where('storage_point_id', $fromStoragePoint->id)
            ->where('doc_type',CommonHelper::RECEIPT)
            ->whereIn('item_uid',$request->packet_ids)
            ->whereNull('utilized_id')
            ->whereNotNull('storage_point_id')
            ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'item_ids' => 'No valid items found for transfer.',
            ]);
        }

        $validPackets = $items->pluck('item_uid')->toArray();
        $invalidPackets = array_diff($request->packet_ids, $validPackets);

        if (!empty($invalidPackets)) {
            throw ValidationException::withMessages([
                'packet_ids' => ['Invalid or mismatched packet IDs: ' . implode(', ', $invalidPackets)],
            ]);
        }
        
        // Get Weights
        $itemIds = $items->pluck('item_id')->unique()->toArray();
        $itemWeight = StoragePointHelper::getItemsWeight($itemIds);
        if($itemWeight['status'] == "error"){
            throw ValidationException::withMessages([
                'packet_ids' => $itemWeight['message'],
            ]);
        }

        $totalIncomingWeight = 0;
        $totalIncomingVolume = 0;

        foreach ($items as $item) {
            $mapped = StoragePointHelper::isStorageNumberMappedToItem(
                $item->item_id,
                $request->to_storage_number,
                $fromStoragePoint->store_id,
                $fromStoragePoint->sub_store_id
            );

            if (!$mapped) {
                throw ValidationException::withMessages([
                    'to_storage_number' => "Storage point is not mapped to packet ID: {$item->item_uid}",
                ]);
            }

            // Check if item exists in weights array
            if (!isset($itemWeight['data'][$item->item_id])) {
                $invalidItems[] = $item->item_id;
                continue;
            }

            // Check if packet exists
            if (!isset($itemWeight['data'][$item->item_id][$item->packet_no])) {
                $invalidItems[] = $item->item_id;
                continue;
            }

            // Calculate incoming weight
            $data = $itemWeight['data'][$item->item_id][$item->packet_no];
            $packetWeight = $data['packetWeight'];
            $packetVolume = $data['packetVolume'];

            $totalIncomingWeight += $packetWeight;
            $totalIncomingVolume += $packetVolume;
        }

        // Throw error if invalid items found
        if (!empty($invalidItems)) {
            throw ValidationException::withMessages([
                'item_ids' => 'Invalid items: ' . implode(', ', $invalidItems)
            ]);
        }

        // Check if weight limits are exceeded
        if (!is_null($maxWeight) && ($currentWeight + $totalIncomingWeight) > $maxWeight) {
            throw ValidationException::withMessages([
                'to_storage_number' => ["Storage point weight limit exceeded. Max: {$maxWeight}, Current: {$currentWeight}, Incoming: {$totalIncomingWeight}"],
            ]);
        }

        // Check if volume limits are exceeded
        if (!is_null($maxVolume) && ($currentVolume + $totalIncomingVolume) > $maxVolume) {
            throw ValidationException::withMessages([
                'to_storage_number' => ["Storage point volume limit exceeded. Max: {$maxVolume}, Current: {$currentVolume}, Incoming: {$totalIncomingVolume}"],
            ]);
        }

        // Proceed with transfer
        \DB::beginTransaction();
        try {
            $user = Helper::getAuthenticatedUser();

            (new WhmJob())->binTransfer($items, $toStoragePoint->id, $user->id);

            $addResponse = StoragePointHelper::addStorageWeight($toStoragePoint->id, $totalIncomingWeight, $totalIncomingVolume);
            if($addResponse['status'] == "error"){
                throw ValidationException::withMessages([
                    'to_storage_number' => $addResponse['message'],
                ]);
            }

            $updateResponse = StoragePointHelper::updateStorageWeight($fromStoragePoint->id, $totalIncomingWeight, $totalIncomingVolume);
            if($updateResponse['status'] == "error"){
                throw ValidationException::withMessages([
                    'to_storage_number' => $updateResponse['message'],
                ]);
            }
            
            \DB::commit();
            return [
                'message' => 'Data transferred successfully.'
            ];
        } catch (\Exception $e) {
            \DB::rollback();
            throw new ApiGenericException($e->getMessage());
        }

    }

    public function validateQr(Request $request){
        $validator = Validator::make($request->all(),[
            'packet_id' => ['required'],
            'storage_point_id' => ['required'],
        ],[
            'packet_id.required' => 'Packet ID are required',
            'storage_point_id.required' => 'Storage point id is required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $item = ErpItemUniqueCode::where('storage_point_id',$request->storage_point_id)
            ->where('item_uid',$request->packet_id)
            ->where('doc_type',CommonHelper::RECEIPT)
            ->whereNull('utilized_id')
            ->whereNotNull('storage_point_id')
            ->select('uid','job_id','morphable_id as putaway_item_id','group_id','company_id','organization_id','book_code','doc_no','doc_date','status','item_id','item_uid','item_name','item_code','item_attributes','status','vendor_id','batch_number','manufacturing_year','expiry_date','serial_no')
            ->first();

        if (!$item) {
            throw ValidationException::withMessages([
                'packet_id' => 'Invalid packet ID for the given storage point.',
            ]);
        }

        return [
            'message' => 'Packet validated successfully.',
            'data' => $item
        ];
    }

    public function validatePoint(Request $request){
        $validator = Validator::make($request->all(),[
            'storage_number' => ['required'],
            'sub_store_id' => ['required'],
            'store_id' => ['required'],
        ],[
            'storage_number.required' => 'Storage number is required',
            'sub_store_id.required' => 'Sub store is required',
            'store_id.required' => 'Store is required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $storageNumber = $request->input('storage_number');
        $storagePoint = \DB::table('erp_wh_details')
            ->where('sub_store_id',$request->sub_store_id)
            ->where('store_id',$request->store_id)
            ->where('storage_number', $request->storage_number)
            ->first();

        
        if (!$storagePoint) {
            throw ValidationException::withMessages([
                'storage_number' => ['Storage point not found.'],
            ]);
        }

        return [
            'data' => $storagePoint,
            'message' => "Storage point details fetched successfully.", $storageNumber,
        ];
    }
}
