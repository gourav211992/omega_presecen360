<?php
namespace App\Helpers;

use DB;

use App\Models\StockLedger;
use App\Models\WhDetail;
use App\Models\WhItemMapping;
use App\Models\ErpItem;
use App\Models\MrnDetail;
use App\Models\WHM\ErpItemUniqueCode;

use App\Helpers\ItemHelper;
use App\Helpers\ConstantHelper;
use App\Models\ItemPackagingDetail;

class StoragePointHelper
{
    public function __construct()
    {
    
    }

    public static function getStoragePoints($itemId, $locationId=NULL, $subLocationId=NULL, $page = 1, $perPage = 10)
    {
        $data = array();
        try{
            // 1. Get mappings with fallback on subcategory
            $records = self::getItemMappings($itemId, $locationId, $subLocationId);

            // 2. Fallback if no mappings found
            if ($records->isEmpty() && $locationId) {
                return self::getFallbackStoragePoints($locationId, $subLocationId, $page, $perPage);
            }

            // 3. Parse structure details once for all mappings
            $storagePointIds = self::extractStoragePointIdsFromMappings($records);
            
            // 4: Filter and paginate storage points
            $results = self::filterValidStoragePoints($storagePointIds, $page, $perPage);   

            if ($results->count() > 0) {
                return self::successResponse("Records successfully fetched.", $results);
            } 
            
            return self::errorResponse("No available storage points found.");
        } catch(\Exception $e){
            $data = self::errorResponse($e->getMessage());
            return $data;

        }
    }

    private static function getItemMappings($itemId, $locationId, $subLocationId){
        $records = WhItemMapping::when($locationId, fn($q) => $q->where('store_id', $locationId))
                ->when($subLocationId, fn($q) => $q->where('sub_store_id', $subLocationId))
                ->when($itemId, fn($q) => $q->whereRaw("JSON_CONTAINS(item_id, JSON_QUOTE(?))", [(string) $itemId]))
                ->get();
        
        if ($records->isEmpty()) {

            // Get item's category and sub-category
            $item = ErpItem::find($itemId);
            if ($item && $item->subcategory_id) {

                // Try sub_category_id
                if ($item->subcategory_id) {
                    $records = WhItemMapping::when($locationId, fn($q) => $q->where('store_id', $locationId))
                        ->when($subLocationId, fn($q) => $q->where('sub_store_id', $subLocationId))
                        ->whereRaw("JSON_CONTAINS(category_id, JSON_QUOTE(?))", [(string)$item->subcategory_id])
                        ->get();
                }
            }
        }
        
        return $records;
    }

    private static function getFallbackStoragePoints($locationId, $subLocationId, $page, $perPage)
    {
        $availablePoints = WhDetail::where('store_id', $locationId)
            ->when($subLocationId, fn($q) => $q->where('sub_store_id', $subLocationId))
            ->where('is_storage_point', 1)
            ->where(function ($q) {
                $q->whereNull('max_weight')
                    ->orWhereNull('current_weight')
                    ->orWhereColumn('current_weight', '<', 'max_weight')
                    ->orWhereNull('max_volume')
                    ->orWhereNull('current_volume')
                    ->orWhereColumn('current_volume', '<', 'max_volume');
            })
            ->paginate($perPage, ['*'], 'page', $page);

        if ($availablePoints->count() > 0) {
            return self::successResponse('Fallback: Showing available storage points without mapping.', $availablePoints);
        }
        
        return self::errorResponse("No available storage points found.");
    }

    private static function extractStoragePointIdsFromMappings($records)
    {
        $storagePointIds = [];

        foreach ($records as $record) {
            // $structureDetails = json_decode($record->structure_details, true);
            $structureDetails = is_string($record->structure_details) ? json_decode($record->structure_details, true) : $record->structure_details;

            if (!$structureDetails) continue;

            // Get the last level-values
            $lastLevel = end($structureDetails);
            $lastLevelValues = $lastLevel['level-values'] ?? [];

            // Get last-level storage points if defined
            if (!empty($lastLevelValues)) {
                $details = WhDetail::whereIn('id', $lastLevelValues)
                ->get()
                ->keyBy('id');

                $hasLastLevel = $details->contains(fn($d) => $d->is_last_level == 1);
                if ($hasLastLevel) {
                    $storagePointIds = array_merge($storagePointIds, array_keys($details->toArray()));
                    continue;
                }

            }

            // Otherwise, find valid children recursively
            foreach ($structureDetails as $level) {
                if (!empty($level['level-values']) && is_array($level['level-values'])) {
                    foreach ($level['level-values'] ?? [] as $val) {
                        $detail = WhDetail::find($val);
                        if ($detail && $detail->is_storage_point == 1 && self::hasSpace($detail)) {
                            $storagePointIds[] = $detail->id;
                        }

                        $childIds = self::findChildStoragePoints($val);
                        $storagePointIds = array_merge($storagePointIds, $childIds);
                    }
                }
            }
        }

        $storagePointIds = array_unique($storagePointIds); 
        return $storagePointIds;
    }

    private static function filterValidStoragePoints(array $ids, $page = 1, $perPage = 10)
    {
        $details = WhDetail::whereIn('id', array_unique($ids))
            ->where(function($q) {
                $q->whereNull('max_weight')
                ->orWhereNull('current_weight')
                ->orWhereColumn('current_weight', '<', 'max_weight')
                ->orWhereNull('max_volume')
                ->orWhereNull('current_volume')
                ->orWhereColumn('current_volume', '<', 'max_volume');
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return $details;
    }

    private static function findChildStoragePoints($parentId, &$visited = [])
    {
        $results = [];

        // Agar node already visited hai, cycle detect ho gayi
        if (in_array($parentId, $visited)) {
            return [];
        }

        // Mark current node as visited
        $visited[] = $parentId;

        $children = WhDetail::where('parent_id', $parentId)
            ->get();

        foreach ($children as $child) {
            if ($child->is_storage_point == 1 && self::hasSpace($child)) {
                $results[] = $child->id;
            } else {
                $results = array_merge($results, self::findChildStoragePoints($child->id, $visited));
            }
        }

        return $results;
    }

    private static function hasSpace($detail)
    {
        return (
            (is_null($detail->max_weight) || is_null($detail->current_weight) || $detail->current_weight < $detail->max_weight)
            ||
            (is_null($detail->max_volume) || is_null($detail->current_volume) || $detail->current_volume < $detail->max_volume)
        );
    }

    // Save Storage Points
    public static function saveStoragePoints($documentHeader, $documentDetailId = NULL, $bookType, $documentStatus, $transactionType = NULL, $stockReservation = NULL, $subStoreId = NULL)
    {
        $data = array();
        try{
            if(empty($documentDetailId)){
                $message = "No storage points found.";
                $data = self::errorResponse($message);
                return $data;
            }

            // dd($documentHeader->id,$documentDetailId, $documentHeader->store_id, $documentHeader->sub_store_id,$bookType);
            $stockLedger = StockLedger::withDefaultGroupCompanyOrg()
                ->where('document_header_id',$documentHeader->id)
                ->whereIn('document_detail_id',$documentDetailId)
                ->where('store_id',$documentHeader->store_id)
                ->where('sub_store_id',$subStoreId)
                ->where('book_type','=',$bookType)
                ->whereIn('document_status', ConstantHelper::DOCUMENT_STATUS_APPROVED)
                ->whereNull('utilized_id')
                ->get();
            
            if(empty($stockLedger)){
                $message = "Stock Ledger not found.";
                $data = self::errorResponse($message);
                return $data;
            }

            foreach($stockLedger as $val){
                $mrnDetail = MrnDetail::find($val->document_detail_id);
                if(!$mrnDetail) {
                    continue;
                }

                $storageUomCount = intval(optional($mrnDetail->item)->storage_uom_count);
                $totalPacket = $storageUomCount > 0 ? $storageUomCount : 1;

                $scannedPacketCount = ErpItemUniqueCode::where('morphable_id',$val->document_detail_id)
                        ->where('trns_type',$bookType)
                        ->where('status',CommonHelper::SCANNED)
                        ->whereNull('utilized_id')
                        ->count();

                $qty = $totalPacket == 0 ? 0 : $scannedPacketCount / $totalPacket;

                $orderQty =  ItemHelper::convertToAltUom($mrnDetail->item_id, $mrnDetail->uom_id, $qty ?? 0);
                $mrnDetail->inventory_uom_qty = $qty;
                $mrnDetail->order_qty = $orderQty;
                $mrnDetail->save();

                $val->receipt_qty = $mrnDetail->inventory_uom_qty;
                $val->putaway_pending_qty = 0;
                $val->save();
                
            }
            
            $message = "Storage points saved successfully.";
            $data = self::successResponse($message, $stockLedger);
            return $data;
        } catch(\Exception $e){
            $data = self::errorResponse($e->getMessage());
            return $data;
        }
    }

    // Error Response
    private static function errorResponse($message)
    {
        return [
            "status" => "error",
            "code" => "500",
            "message" => $message,
            "data" => null,
        ];
    }

    // Success Response
    private static function successResponse($response,$data)
    {
        return [
            "status" => "success",
            "code" => "200",
            "message" => $response,
            "data" => $data
        ];
    }

    // public static function getStoragePointsForMultipleItems(array $itemIds, $locationId = null, $subLocationId = null)
    // {
    //     try {
    //         if (empty($itemIds)) {
    //             return self::errorResponse("Item Ids required.");
    //         }

    //         if (empty($locationId)) {
    //             return self::errorResponse("Location Id required.");
    //         }

    //         // Step 1: Fetch all item mappings
    //         $mappings = WhItemMapping::where('store_id', $locationId)
    //             ->where(function ($q) use ($itemIds) {
    //                 foreach ($itemIds as $itemId) {
    //                     $q->orWhereRaw("JSON_CONTAINS(item_id, JSON_QUOTE(?))", [(string)$itemId]);
    //                 }
    //             })
    //             ->when($subLocationId, fn($q) => $q->where('sub_store_id', $subLocationId))
    //             ->get();

    //         // Step 2: If not found, try subcategory/category fallback
    //         if ($mappings->isEmpty()) {
    //             $items = ErpItem::whereIn('id', $itemIds)->get();

    //             $subcategoryIds = $items->pluck('subcategory_id')->filter()->unique()->toArray();

    //             if (!empty($subcategoryIds)) {
    //                 $mappings = WhItemMapping::where('store_id', $locationId)
    //                     ->when($subLocationId, fn($q) => $q->where('sub_store_id', $subLocationId))
    //                     ->where(function ($q) use ($subcategoryIds) {
    //                         foreach ($subcategoryIds as $subId) {
    //                             $q->orWhereRaw("JSON_CONTAINS(category_id, JSON_QUOTE(?))", [(string)$subId]);
    //                         }
    //                     })->get();
    //             }
    //         }

    //         // Step 2.5: If still no mapping, fallback to all available storage points in the given store
    //         if ($mappings->isEmpty() && $locationId) {
    //             $fallbackStoragePoints = WhDetail::where('store_id', $locationId)
    //                 ->when($subLocationId, fn($q) => $q->where('sub_store_id', $subLocationId))
    //                 ->where('is_storage_point', 1)
    //                 ->get();
                
    //             $availablePoints = $fallbackStoragePoints->filter(function ($detail) {
    //                 return self::hasSpace($detail);
    //             });

    //             if ($availablePoints->isNotEmpty()) {
    //                 $data = self::successResponse('Fallback: Showing available storage points without mapping.', $availablePoints->values());
    //                 return $data;
    //             }
    //         }

    //         // Step 3: Parse structure_details
    //         $storagePointIds = [];

    //         foreach ($mappings as $record) {
    //             // $structureDetails = json_decode($record->structure_details, true);
    //             $structureDetails = is_string($record->structure_details) ? json_decode($record->structure_details, true) : $record->structure_details;

    //             if (!$structureDetails) continue;

    //             // Get the last level-values
    //             $lastLevel = end($structureDetails);
    //             $lastLevelValues = $lastLevel['level-values'] ?? [];

    //             // Get last-level storage points if defined
    //             if (!empty($lastLevelValues)) {
    //                 $details = WhDetail::whereIn('id', $lastLevelValues)
    //                 ->get()
    //                 ->keyBy('id');

    //                 $hasLastLevel = $details->contains(fn($d) => $d->is_last_level == 1);
    //                 if ($hasLastLevel) {
    //                     $storagePointIds = array_merge($storagePointIds, array_keys($details->toArray()));
    //                     continue;
    //                 }

    //             }

    //             // Otherwise, find valid children recursively
    //             $allDetailIds = [];
    //             foreach ($structureDetails as $level) {
    //                 if (!empty($level['level-values']) && is_array($level['level-values'])) {
    //                     $allDetailIds = array_merge($allDetailIds, $level['level-values']);
    //                 }
    //             }

    //             $allDetailIds = array_unique($allDetailIds);
    //             $detailsMap = WhDetail::whereIn('id', $allDetailIds)->get()->keyBy('id');

    //             foreach ($structureDetails as $level) {
    //                 if (!empty($level['level-values']) && is_array($level['level-values'])) {
    //                     foreach ($level['level-values'] ?? [] as $val) {
    //                         $detail = $detailsMap->get($val);
    //                         if ($detail && $detail->is_storage_point == 1 && self::hasSpace($detail)) {
    //                             $storagePointIds[] = $detail->id;
    //                         }

    //                         $childIds = self::findChildStoragePoints($val);
    //                         $storagePointIds = array_merge($storagePointIds, $childIds);
    //                     }
    //                 }
    //             }
    //         }

    //         $storagePointIds = array_unique($storagePointIds); 
            
    //         // Step 4: Fetch matching storage points
    //         $results = self::filterValidStoragePoints($storagePointIds);   

    //         if(!empty($results)){
    //             $message = "Records successfuly fetched.";
    //             $data = self::successResponse($message, $results);
    //         } else{
    //             $message = "No available storage points found.";
    //             $data = self::errorResponse($message);
    //         }   
    //         return $data;
    //     } catch (\Exception $e) {
    //         return self::errorResponse($e->getMessage());
    //     }
    // }

     public static function getStoragePointsForMultipleItems(array $itemIds, $locationId=NULL, $subLocationId=NULL, $page = 1, $perPage = 10)
    {
        $data = array();
        try{
            // 1. Get mappings with fallback on subcategory
            $records = self::getMultipleItemMappings($itemIds, $locationId, $subLocationId);

            // 2. Fallback if no mappings found
            if ($records->isEmpty() && $locationId) {
                return self::getFallbackStoragePoints($locationId, $subLocationId, $page, $perPage);
            }

            // 3. Parse structure details once for all mappings
            $storagePointIds = self::extractStoragePointIdsFromMappings($records);
            
            // 4: Filter and paginate storage points
            $results = self::filterValidStoragePoints($storagePointIds, $page, $perPage);   

            if ($results->count() > 0) {
                return self::successResponse("Records successfully fetched.", $results);
            } 
            
            return self::errorResponse("No available storage points found.");
        } catch(\Exception $e){
            $data = self::errorResponse($e->getMessage());
            return $data;

        }
    }

    private static function getMultipleItemMappings($itemIds, $locationId, $subLocationId){
        $records = WhItemMapping::where('store_id', $locationId)
                ->when($subLocationId, fn($q) => $q->where('sub_store_id', $subLocationId))
                ->where(function ($q) use ($itemIds) {
                    foreach ($itemIds as $itemId) {
                        $q->orWhereRaw("JSON_CONTAINS(item_id, JSON_QUOTE(?))", [(string)$itemId]);
                    }
                })
                ->get();
        
        if ($records->isEmpty()) {
            $items = ErpItem::whereIn('id', $itemIds)->get();
            $subcategoryIds = $items->pluck('subcategory_id')->unique()->toArray();

            if (!empty($subcategoryIds)) {
                $records = WhItemMapping::where('store_id', $locationId)
                    ->when($subLocationId, fn($q) => $q->where('sub_store_id', $subLocationId))
                    ->where(function ($q) use ($subcategoryIds) {
                        foreach ($subcategoryIds as $subId) {
                            $q->orWhereRaw("JSON_CONTAINS(category_id, JSON_QUOTE(?))", [(string)$subId]);
                        }
                    })->get();
            }
        }
        
        return $records;
    }


    // Get Specific Storage Point Detail
    public static function getStoragePointDetail($storageNumber)
    {
        try {
            if (!$storageNumber) {
                return self::errorResponse("Storage number is required.");
            }

            // Fetch the storage point
            $storagePoint = WhDetail::where('storage_number', $storageNumber)->first();

            if (!$storagePoint) {
                return self::errorResponse("Storage point not found.");
            }

            return self::successResponse("Storage point details fetched successfully.", $storagePoint);

        } catch (\Exception $e) {
            return self::errorResponse($e->getMessage());
        }
    }

    public static function getStoragePointDetailById($storagePointId)
    {
        try {
            if (!$storagePointId) {
                return self::errorResponse("Storage point ID is required.");
            }

            // Fetch by ID
            $storagePoint = WhDetail::where('id', $storagePointId)
                ->select('id','heirarchy_name','name','max_weight','max_volume','current_weight','current_volume','storage_number','parent_id')
                ->first();

            if (!$storagePoint) {
                return self::errorResponse("Storage point not found.");
            }

            return self::successResponse("Storage point details fetched successfully.", $storagePoint);

        } catch (\Exception $e) {
            return self::errorResponse($e->getMessage());
        }
    }

    // get packet weight & volume
    public static function getItemWeight($itemId,$packetNo){
        $item = ErpItem::find($itemId);
        if (!$item) {
            return self::errorResponse("Invalid item.");
        }
        $packetWeight = $item->storage_weight ?? 0;
        $packetVolume = $item->storage_volume ?? 0;
        $storageUomCount = intval($item->storage_uom_count);
        if ($storageUomCount > 1) {
            $packagingDetail = \DB::table('erp_item_packaging_details')
                ->where('item_id', $item->id)
                ->where('packet_no', $packetNo)
                ->whereNull('deleted_at')
                ->first();

            $packetWeight = $packagingDetail ? $packagingDetail->storage_weight : 0;
            $packetVolume = $packagingDetail ? $packagingDetail->storage_volume : 0;
        }

        $response = [
            "packetWeight" => $packetWeight,
            "packetVolume" => $packetVolume
        ];

        return self::successResponse("Storage point weight found successfully.", $response);
    }

    public static function addStorageWeight($storagePointId, $totalIncomingWeight, $totalIncomingVolume){
        if (!$storagePointId) {
            return self::errorResponse("Storage point ID is required.");
        }

        $storagePoint = WhDetail::find($storagePointId);
        $currentWeight = $storagePoint->current_weight;
        $currentVolume = $storagePoint->current_volume;
        $maxWeight = $storagePoint->max_weight ?? null;
        $maxVolume = $storagePoint->max_volume ?? null;
        
        if (!is_null($maxWeight) && ($currentWeight + $totalIncomingWeight) > $storagePoint->max_weight) {
            return self::errorResponse("Storage point weight limit exceeded. Max: {$maxWeight}, Current: {$currentWeight}, Incoming: {$totalIncomingWeight}");
        }
        
        if (!is_null($maxVolume) && ($currentVolume + $totalIncomingVolume) > $maxVolume) {
            return self::errorResponse("Storage point volume limit exceeded. Max: {$maxVolume}, Current: {$currentVolume}, Incoming: {$totalIncomingVolume}");
        }

       
        $storagePoint->current_weight = $currentWeight + $totalIncomingWeight;
        $storagePoint->current_volume = $currentVolume + $totalIncomingVolume;
        $storagePoint->save();

        return self::successResponse("Storage point weight updated successfully.", $storagePoint);

    }

    public static function updateStorageWeight($storagePointId, $weight, $volume){
        if (!$storagePointId) {
            return self::errorResponse("Storage point ID is required.");
        }

        $storagePoint = WhDetail::find($storagePointId);

        if (!$storagePoint) {
            return self::errorResponse("Invalid storage point.");
        }

        $currentWeight = $storagePoint->current_weight ?? 0;
        $currentVolume = $storagePoint->current_volume ?? 0;
       
        $storagePoint->current_weight = $currentWeight > $weight ? $currentWeight - $weight : 0;
        $storagePoint->current_volume = $currentVolume > $volume ? $currentVolume - $volume : 0;
        $storagePoint->save();

        return self::successResponse("Storage point weight updated successfully.", $storagePoint);

    }

    public static function isStorageNumberMappedToItem($itemId, $storageNumber, $storeId = null, $subStoreId = null)
    {
        // Get mappings for item (with fallback on subcategory if needed)
        $records = self::getItemMappings($itemId, $storeId, $subStoreId);
        
        if ($records->isEmpty()) {
            // Fallback: check fallback storage points for presence of storageNumber
            $fallbackPoints = WhDetail::where('store_id', $storeId)
            ->when($subStoreId, fn($q) => $q->where('sub_store_id', $subStoreId))
            ->where('is_storage_point', 1)
            ->where('storage_number', $storageNumber)
            ->where(function ($q) {
                $q->whereNull('max_weight')
                    ->orWhereNull('current_weight')
                    ->orWhereColumn('current_weight', '<', 'max_weight')
                    ->orWhereNull('max_volume')
                    ->orWhereNull('current_volume')
                    ->orWhereColumn('current_volume', '<', 'max_volume');
            })
            ->exists();

            return $fallbackPoints;
        }

        // Extract storage point IDs from mappings
        $storagePointIds = self::extractStoragePointIdsFromMappings($records);

        if (empty($storagePointIds)) {
            return false;
        }

        // Finally check if $storageNumber exists in those storage points
        $exists = WhDetail::whereIn('id', array_unique($storagePointIds))
            ->where(function($q) {
                $q->whereNull('max_weight')
                ->orWhereNull('current_weight')
                ->orWhereColumn('current_weight', '<', 'max_weight')
                ->orWhereNull('max_volume')
                ->orWhereNull('current_volume')
                ->orWhereColumn('current_volume', '<', 'max_volume');
            })
            ->where('storage_number', $storageNumber)
            ->exists();

        return $exists;
    }

    public static function getItemsWeight(array $itemIds)
    {
        // Fetch items in bulk
        $items = ErpItem::select('id','storage_weight','storage_volume','storage_uom_count')
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        // Fetch all packaging details for items that have multiple UOMs
        $packagingDetails = ItemPackagingDetail::whereIn('item_id', $itemIds)
            ->get()
            ->groupBy('item_id');

        $result = [];

        foreach ($itemIds as $itemId) {
            $item = $items[$itemId] ?? null;

            if (!$item) {
                return self::errorResponse("Invalid item: {$itemId}");
            }

            $storageUomCount = intval($item->storage_uom_count ?? 1); // default 1

            if ($storageUomCount > 1) {
                // Multiple UOM items, fetch all packets
                $packets = $packagingDetails[$itemId] ?? [];

                foreach ($packets as $packet) {
                    $result[$itemId][$packet->packet_no] = [
                        'packetWeight' => $packet->storage_weight ?? 0,
                        'packetVolume' => $packet->storage_volume ?? 0
                    ];
                }
            }else{
                $result[$itemId][1] = [
                    'packetWeight' => $item->storage_weight ?? 0,
                    'packetVolume' => $item->storage_volume ?? 0
                ];
            }
        }

        return self::successResponse("Storage point weight updated successfully.", $result);
    }

}
