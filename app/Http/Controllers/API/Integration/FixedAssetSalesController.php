<?php

namespace App\Http\Controllers\API\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FixedAssetRegistration;
use App\Models\FixedAssetSub;
use App\Models\MrnDetail;
use Illuminate\Support\Facades\DB;

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
            $request->validate([
                'item_code' => 'required|string',
                'asset_code' => 'required|string'
            ]);

            $itemCode = $request->input('item_code');
            $assetCode = $request->input('asset_code');

            // Find the asset using the same logic as getAssetCode
            $asset = $this->findAssetByItemCode($itemCode);

            if (!$asset) {
                return response()->json([
                    'status' => false,
                    'message' => 'Asset not found for the provided item code',
                    'data' => null
                ], 404);
            }

            // Verify the asset_code matches
            if ($asset->asset_code !== $assetCode) {
                return response()->json([
                    'status' => false,
                    'message' => 'Asset code mismatch for the provided item code',
                    'data' => null
                ], 400);
            }

            // Get sub-assets for this asset
            $subAssets = $asset->subAsset()
                ->select('id', 'sub_asset_code')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Sub-assets retrieved successfully',
                'data' => [
                    'asset_code' => $asset->asset_code,
                    'asset_name' => $asset->asset_name,
                    'item_code' => $itemCode,
                    'sub_assets' => $subAssets,
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
        $asset = FixedAssetRegistration::where('item_code', $itemCode)->first();

        if ($asset) {
            return $asset;
        }

        // Second case: If not found, query mrn_details table
        $mrnDetail = MrnDetail::where('item_code', $itemCode)
            ->select('item_id', 'mrn_header_id', 'id as mrn_detail_id','item_code')
            ->first();

        if (!$mrnDetail) {
            return null;
        }

        // Find the asset using mrn_detail_id and mrn_header_id
        $asset = FixedAssetRegistration::where('mrn_detail_id', $mrnDetail->mrn_detail_id)
            ->where('mrn_header_id', $mrnDetail->mrn_header_id)
            ->first();

        return $asset;
    }
}
