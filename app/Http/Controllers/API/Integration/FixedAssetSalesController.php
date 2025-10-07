<?php

namespace App\Http\Controllers\API\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FixedAssetRegistration;
use App\Models\FixedAssetSub;
use App\Models\MrnDetail;
use App\Models\MrnAssetDetail;
use Illuminate\Support\Facades\DB;
use App\Models\AuthUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Helpers\Helper;
use Carbon\Carbon;
use P360\Core\Models\AuthUser as P360AuthUser;

class FixedAssetSalesController extends Controller
{
    /**
     * Get asset code by item code (POST request)
     *
     * First checks if item_code exists in fixed_asset_registration table,
     * if found returns asset_code, otherwise queries mrn_details table
     * to find related asset information
     */
    public function getAssetCode(Request $request)
    {
        try {
            $request->validate([
                'item_code' => 'required|string'
            ]);

            $itemCode = $request->input('item_code');

            // First case: Check if item_code exists in fixed_asset_registration table
            $asset = FixedAssetRegistration::where('item_code', $itemCode)->first();

            if ($asset) {
                return response()->json([
                    'status' => true,
                    'message' => 'Asset found in registration table',
                    'data' => [
                        'asset_code' => $asset->asset_code,
                        'asset_name' => $asset->asset_name,
                        'item_code' => $asset->item_code
                    ]
                ]);
            }

            // Second case: If not found, query mrn_details table
            $mrnDetail = MrnDetail::where('item_code', $itemCode)
                ->select('item_id', 'mrn_header_id', 'id as mrn_detail_id','item_code')
                ->first();

            if (!$mrnDetail) {
                return response()->json([
                    'status' => false,
                    'message' => 'Item code not found in MRN details',
                    'data' => null
                ], 404);
            }

            // Find the asset using mrn_detail_id and mrn_header_id
            $asset = FixedAssetRegistration::where('mrn_detail_id', $mrnDetail->mrn_detail_id)
                ->where('mrn_header_id', $mrnDetail->mrn_header_id)
                ->first();

            if (!$asset) {
                return response()->json([
                    'status' => false,
                    'message' => 'No asset found for this item code',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Asset found through MRN details',
                'data' => [
                    'asset_code' => $asset->asset_code,
                    'asset_name' => $asset->asset_name,
                    'item_code' => $mrnDetail->item_code
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get sub-assets by item_code and asset_code (POST request)
     *
     * Uses the same item_code lookup logic as getAssetCode,
     * then returns all sub-assets for the found asset
     */
    public function getSubAssets(Request $request)
    {
        try {
           
            // Validate request
            $request->validate([
                'item_code' => 'required|string',
                'asset_code' => 'required|string',
            ]);

            // Get values
            $itemCode = $request->input('item_code');
            $assetCode = $request->input('asset_code');
           
            // Find the asset using the same logic as getAssetCode
            $asset = $this->findAssetByItemCode($itemCode);
            if (!$asset || count($asset) == 0) 
            {
                return response()->json([
                    'status' => false,
                    'message' => 'Asset not found for the provided item code',
                    'data' => null
                ], 404);
            }

            $asset = array_filter($asset);

            // Verify the asset_code matches
           if (!in_array($assetCode, $asset))
            {
                return response()->json([
                    'status' => false,
                    'message' => 'Asset code mismatch for the provided item code',
                    'data' => null
                ], 400);
            }

            $assetcodeexist = MrnAssetDetail::where('asset_code',$assetCode)->first();

            // Get sub-assets for this asset
           $subassets = FixedAssetSub::where('parent_id',$assetcodeexist->asset_id)->pluck('sub_asset_code')->toArray();

            return response()->json([
                'status' => true,
                'message' => 'Sub-assets retrieved successfully',
                'data' => [
                    'asset_code' => $assetCode,
                    'asset_name' => $assetcodeexist->asset_name,
                    'item_code' => $itemCode,
                    'sub_assets' => $subassets,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }


    /**
     * Helper method to find asset by item_code using the same logic as getAssetCode
     */
    private function findAssetByItemCode($itemCode)
    {
        
        // First case: Check if item_code exists in fixed_asset_registration table
        $model = new FixedAssetRegistration();
        $table = $model->getTable(); // Get table name dynamically from model

        // Check if column exists in this table
        if (Schema::hasColumn($table, 'item_code')) 
        {
            $asset = FixedAssetRegistration::where('item_code', $itemCode)->first();
        }
        if (!empty($asset)) 
        {
            return $asset;
        }
      

        // Second case: If not found, query mrn_details table
        $mrnDetail = MrnDetail::where('item_code', $itemCode)
            ->select('item_id', 'mrn_header_id', 'id as mrn_detail_id','item_code')
            ->get();
        if (!$mrnDetail || count( $mrnDetail) == 0) 
        {
            return null;
        }

        $asset = [];


        foreach($mrnDetail as $detail)
        {
             // Find the asset using mrn_detail_id and mrn_header_id
            $asset[] = MrnAssetDetail::where('detail_id', $detail->mrn_detail_id)
                ->where('header_id', $detail->mrn_header_id)
                ->pluck('asset_code')->first();
        }
    
        return $asset;
    }

    public function getAssetValues(Request $request)
    {
         try {
            $request->validate([
                'asset_code' => 'required|string',
                'sub_assets' => 'required|array'
            ]);

           $assetcode = $request->input('asset_code');
           $subassetcode = $request->input('sub_assets');
           $date = $request->input('date');

           $assetcodeexist = MrnAssetDetail::where('asset_code',$assetcode)->first();

            if (!$assetcodeexist) 
            {
                return response()->json([
                    'status' => false,
                    'message' => 'Asset not found',
                    'data' => null
                ], 404);
            }

            $subassets = FixedAssetSub::where('parent_id',$assetcodeexist->asset_id)->pluck('sub_asset_code')->toArray();
            $missing = array_diff($subassets, $subassetcode);
             
            if (!$missing) 
            {
                return response()->json([
                    'status' => false,
                    'message' => 'Some Sub asset value are not present in database',
                    'data' => "Missing values: " . implode(', ', $missing)
                ], 404);
            }

            $data = [];
            $toDateObj = $date;

            $subassetsdata = FixedAssetSub::whereIn('sub_asset_code',$subassetcode)->get();
            $financialEndDate = Helper::getFinancialYear(date('Y-m-d'))['end_date'];
            $financialStartDate = Helper::getFinancialYear(date('Y-m-d'))['start_date'];
            $assetdatadetail = DB::table('erp_finance_fixed_asset_registration')->where('id',$assetcodeexist->asset_id)->first();
            
            foreach ($subassetsdata as $subasset) {
                 
                $subassetcode = $subasset->sub_asset_code;
                $from_date = $subasset->last_dep_date;

                // Parse dates using Carbon
                $fromDateObjCap = Carbon::parse($subasset->capitalize_date);
                $expiryDate = Carbon::parse($subasset->expiry_date);
                $toDateObj = Carbon::parse($toDateObj); // assuming $toDateObj is already a date or string
                $fromDateObj = Carbon::parse($from_date);
                
                // Check expiry
                $expire = false;
                if ($expiryDate->lessThanOrEqualTo($toDateObj)) {
                    $toDateObj = $expiryDate;
                    $to_date = $expiryDate->format('Y-m-d');
                    $expire = true;
                } else {
                    $to_date = $toDateObj->format('Y-m-d');
                }

                $subdata['to_date'] = $to_date;

                // Calculate days difference
                $diffDays = $toDateObj->diffInDays($fromDateObj) + 1;
                
                if ($diffDays > 0) {
                    $depType = $assetdatadetail->depreciation_method;
            
                    // Determine current value
                    if ($depType === "SLM") {
                        $value = $subasset->current_value;
                    } else {
                        $isCurrent = (
                            Carbon::parse($subasset->capitalize_date)->between(
                                Carbon::parse($financialStartDate),
                                Carbon::parse($financialEndDate)
                            )
                        );

                        $value = $isCurrent
                            ? $subasset->current_value
                            : $subasset->current_value_after_dep;
                    }

                    // Depreciation calculation
                    $totalDepreciation = (
                        ($assetdatadetail->depreciation_percentage / 100) * $value
                    ) * ($diffDays / 365);

                    $after_dep_value = $subasset->current_value_after_dep - $totalDepreciation;
                    $salv = $subasset->salvage_value;
                    $diff = $after_dep_value - $salv;

                    if ($expire && $diff > 0.0 && $depType === "WDV") {
                    
                        $totalDepreciation += $diff;
                        $after_dep_value = $subasset->current_value_after_dep - $totalDepreciation;
                    }

                    // Posted days
                    $posted_days = 0;
                    if (!empty($assetcodeexist->dep_type) && $assetcodeexist->dep_type !== $dep_type) {
                        $capitalizeDate = Carbon::parse($subasset->capitalize_date);
                        $lastDepDate = Carbon::parse($subasset->last_dep_date);
                        $posted_days = $lastDepDate->diffInDays($capitalizeDate);
                    }
                    

                    $assetData = [
                        'asset_id' => $assetcodeexist->id,
                        // 'category' => $assetcodeexist->category->name ?? '',
                        'asset_code' => $assetcodeexist->asset_code,
                        'sub_asset_code' => $subasset->sub_asset_code,
                        'sub_asset_id' => $subasset->id,
                        'asset_name' => $assetcodeexist->asset_name,
                        // 'ledger_name' => $assetcodeexist->ledger->name ?? '',
                        'from_date' => $from_date,
                        'to_date' => $to_date,
                        'posted_days' => $posted_days,
                        'days' => $diffDays,
                        'current_value' => $subasset->current_value,
                        'current_value_after_dep' => $subasset->current_value_after_dep,
                        'dep_amount' => $totalDepreciation +  $subasset->total_depreciation,
                        'after_dep_value' => $after_dep_value - $subasset->total_depreciation,
                    ];

                    $data[] = $assetData;
                }
            }
              return response()->json([
                    'status' => true,
                    'message' => 'Asset values fetched successfully',
                    'data' => $data
                ], 200);
                
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
