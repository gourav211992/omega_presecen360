<?php

namespace App\Helpers;

use App\Models\FixedAssetRegistration;
use App\Models\FixedAssetSub;

class FinanceModule
{
    public static function getFixedAssetRDV($uid = null, $asset_code = null, $sub_asset_code = null, $asset_name = null)
    {
        try {
            $assetSub = null;
            $parentAsset = null;

            // 1 Case: UID provided (using id of FixedAssetSub)
            if (!empty($uid)) {
                $assetSub = FixedAssetSub::where('uid',$uid)->first();
               

                if (!$assetSub) {
                    return [
                        'success' => false,
                        'message' => 'Asset not found with the provided UID',
                        'data'    => null
                    ];
                }

                $parentAsset = $assetSub->asset; // relation from sub to parent
            }

            // 2 Case: asset_code + sub_asset_code provided
            elseif (!empty($sub_asset_code)) {
                $assetSub = FixedAssetSub::where('sub_asset_code', $sub_asset_code)->first();

                if (!$assetSub) {
                    return [
                        'success' => false,
                        'message' => 'Asset not found with the provided sub_asset_code',
                        'data'    => null
                    ];
                }

                $parentAsset = $assetSub->asset;
            }

            // 3️ Case: only asset_code provided
            elseif (!empty($asset_code) && !empty($asset_name)) {
                $parentAsset = FixedAssetRegistration::where('asset_code', $asset_code)->where('asset_name',$asset_name)->first();

                if (!$parentAsset) {
                    return [
                        'success' => false,
                        'message' => 'Asset not found with the provided asset_code & asset_name',
                        'data'    => null
                    ];
                }
            }

            else {
                return [
                    'success' => false,
                    'message' => 'Either UID or asset_code (with optional sub_asset_code) is required',
                    'data'    => null
                ];
            }

            if (!$parentAsset) {
                return [
                    'success' => false,
                    'message' => 'Parent asset not found',
                    'data'    => null
                ];
            }

            $rdvValue = null;
            if (!empty($parentAsset->capitalize_date) && !empty($parentAsset->current_value)) {
                $rdvValue = \App\Http\Controllers\FixedAsset\ITDepreciationController::getIncomeTaxRDV(
                    $parentAsset->capitalize_date,
                    $parentAsset->depreciation_percentage ?? 0,
                    $parentAsset->current_value
                );
            }

            return [
                'success' => true,
                'message' => 'RDV calculated successfully',
                'rdv'            => $rdvValue,
                'data'    => [
                    'rdv'            => $rdvValue,
                    'asset_code'     => $asset_code ?? ($parentAsset->asset_code ?? null),
                    'capitalize_date'=> $parentAsset->capitalize_date ?? null,
                    'base_value'     => $parentAsset->current_value ?? null,
                ]
            ];

        } catch (\Exception $e) {
            \Log::error('Error in getFixedAssetRDV: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'An error occurred while calculating RDV',
                'data'    => null,
                'error'   => $e->getMessage()
            ];
        }
    }

    public static function mrnAssetRegister($mrn_id, $alias): array
    {

        DB::beginTransaction();
        try {
            Log::info('mrn register', [
                'mrn_id' => $mrn_id,
                'alias' => $alias,
                'constantalisa' => ConstantHelper::PB_SERVICE_ALIAS
            ]);

            if (!empty($alias) && ($alias == ConstantHelper::PB_SERVICE_ALIAS)) {
                Log::error('pbheader get');
                $mrn_id = PbHeader::where('id', $mrn_id)->pluck('mrn_header_id')->first();
            } else {
                $mrn_id = $mrn_id;
            }
            $assets = MrnHeader::where('id', $mrn_id)
                ->whereHas('items', function ($q) {
                    $q->where('basic_value', '>', 0) // must have positive basic_value
                        ->whereHas('item', function ($q) {
                            $q->where('is_asset', 1); // must be an asset
                        })
                        ->doesntHave('asset'); // must not have linked asset
                })
                ->exists();

            $mrn_assets = MrnAssetDetail::where('header_id', $mrn_id)->get();


            if ($assets && !$mrn_assets->isEmpty()) {
                $mrn = MrnHeader::find($mrn_id);
                if (empty($mrn)) {
                    DB::rollBack();
                    return [
                        'message' => 'MRN not exist',
                        'status' => false
                    ];
                }

                $user = Helper::getAuthenticatedUser();
                $organization = $user->organization;
                $book = Book::find($mrn->book_id);
                if (empty($book)) {
                    DB::rollBack();
                    return [
                        'message' => 'MRN Book not found',
                        'status' => false
                    ];
                }

                $glPostingBookParam = OrganizationBookParameter::where('book_id', $book->id)
                    ->where('parameter_name', ServiceParametersHelper::GL_POSTING_SERIES_PARAM)
                    ->first();

                if (!isset($glPostingBookParam) || !isset($glPostingBookParam->parameter_value[0])) {
                    DB::rollBack();
                    return [
                        'status' => false,
                        'message' => 'Financial Book Code is not specified',
                        'data' => []
                    ];
                }

                $glPostingBookId = $glPostingBookParam->parameter_value[0];

                foreach ($mrn_assets as $mrn_asset) {
                    $category_id = $mrn_asset->asset_category_id;
                    $asset_name = $mrn_asset->asset_name;
                    $capitalize_date = $mrn_asset->capitalization_date;
                    $life = $mrn_asset->estimated_life;
                    $detail_id = $mrn_asset->detail_id;


                    // Input validation
                    if (empty($mrn_id) || empty($category_id) || empty($asset_name) || empty($capitalize_date) || empty($life) || empty($detail_id)) {
                        DB::rollBack();
                        return [
                            'status' => false,
                            'message' => 'All parameters (mrn_id, category_id, asset_name, capitalize_date, life, detail_id) are required.'
                        ];
                    }

                    // Validate capitalize_date format (Y-m-d)
                    try {
                        $capitalize_date = Carbon::parse($capitalize_date)->format('Y-m-d');
                    } catch (Exception $e) {
                        DB::rollBack();
                        return [
                            'status' => false,
                            'message' => 'Invalid capitalize_date format. Expected format: Y-m-d',
                            'error' => $e->getMessage()
                        ];
                    }

                    // Validate life (should be a positive number)
                    if (!is_numeric($life) || $life <= 0) {
                        DB::rollBack();
                        return [
                            'status' => false,
                            'message' => 'Asset life must be a positive number.'
                        ];
                    }

                    // Validate asset_name
                    if (!is_string($asset_name) || trim($asset_name) === '') {
                        DB::rollBack();
                        return [
                            'status' => false,
                            'message' => 'Asset name must be a non-empty string.'
                        ];
                    }

                    $setup = FixedAssetSetup::where('asset_category_id', $category_id)
                        ->where('act_type', 'company')->first();

                    if (empty($setup)) {
                        DB::rollBack();
                        return [
                            'message' => 'Setup not exist',
                            'status' => false
                        ];
                    }
                    $mrn_detail = MrnDetail::find($detail_id);

                    $exitingReg = FixedAssetRegistration::where('mrn_detail_id', $mrn_detail->id)
                        ->where('mrn_header_id', $mrn->id)->first();

                    if (!empty($exitingReg)) {
                        DB::rollBack();
                        return [
                            'message' => 'MRN already registered with asset code ' . $exitingReg->asset_code,
                            'status' => false
                        ];
                    }

                    if (!empty($existingAsset)) {
                        DB::rollBack();
                        return [
                            'status' => false,
                            'message' => 'Asset Code ' . $existingAsset->asset_code . ' already exists.',
                            'data' => []
                        ];
                    }

                    if (!empty($alias) && ($alias == ConstantHelper::PB_SERVICE_ALIAS)) {
                        Log::error('pb item value set: ' . $mrn_detail->pb_item_value);
                        $currentValue = $mrn_detail->pb_item_value;
                    } else {
                        Log::error('basic_value: ' . ($mrn_detail->basic_value + $mrn_detail->header_exp_amount));
                        $currentValue = $mrn_detail->basic_value + $mrn_detail->header_exp_amount;
                    }

                    $depreciationPercentage = $setup->salvage_percentage ?? $organization->dep_percentage ?? null;
                    $salvageValue = round($currentValue * ($depreciationPercentage / 100), 2);
                    $method = $organization->dep_method;

                    $depreciationRate = 0;
                    if ($method === 'SLM') {
                        $annualDepreciation = ($currentValue - $salvageValue) / $life;
                        $depreciationRate = round(($annualDepreciation / $currentValue) * 100, 2);
                    } elseif ($method === 'WDV') {
                        $depreciationRate = round((1 - pow($salvageValue / $currentValue, 1 / $life)) * 100, 2);
                    }




                    if (count($mrn_detail->batches) > 0) {
                        $count = count($mrn_detail->batches);
                        $uniqueCodes = $mrn_detail->uniqueCodes->values();
                        $totalqty = 0;
                        foreach ($mrn_detail->batches as $batch) {
                            $totalqty += $batch->inventory_uom_qty;
                        }
                        $singlevalue = round($currentValue / $totalqty, 2);

                        $offset = 0;

                        foreach ($mrn_detail->batches as $batch) {
                            $salvageValue = round(($singlevalue * $batch->inventory_uom_qty) * ($depreciationPercentage / 100), 2);

                            $asset_code = self::generateAssetCode($category_id);
                            $existingAsset = FixedAssetRegistration::where('asset_code', $asset_code)->first();

                            $data = [
                                'organization_id' => $user->organization_id,
                                'group_id' => $organization->group_id,
                                'company_id' => $organization->company_id,
                                'created_by' => $user->id,
                                'type' => get_class($user),
                                'book_id' => $glPostingBookId,
                                'document_number' => $mrn->document_number,
                                'document_date' => $mrn->document_date,
                                'mrn_detail_id' => $mrn_detail->id,
                                'mrn_header_id' => $mrn->id,
                                'asset_code' => $asset_code,
                                'asset_name' => $asset_name,
                                'brand_name' => $mrn_asset->brand_name,
                                'model_no' => $mrn_asset->model_no,
                                'procurement_type' => $mrn_asset->procurement_type,
                                'quantity' => $batch->inventory_uom_qty,
                                'category_id' => $category_id,
                                'reference_doc_id' => $mrn->id,
                                'reference_series' => ConstantHelper::MRN_SERVICE_ALIAS,
                                'ledger_id' => $setup->ledger_id,
                                'ledger_group_id' => $setup->ledger_group_id,
                                'capitalize_date' => $capitalize_date,
                                'last_dep_date' => $capitalize_date,
                                'vendor_id' => $mrn->vendor_id,
                                'currency_id' => $mrn->vendor?->currency_id,
                                'sub_total' => $mrn_detail->basic_value,
                                'tax' => $mrn_detail->tax_value,
                                'purchase_amount' => $mrn_detail->basic_value + $mrn_detail->tax_value,
                                'supplier_invoice_date' => $mrn->supplier_invoice_date,
                                'book_date' => $mrn_detail->created_at ?? null,
                                'supplier_invoice_no' => $mrn->supplier_invoice_no,
                                'location_id' => $mrn->sub_store_id ?? null,
                                'cost_center_id' => $mrn->cost_center_id ?? null,
                                'maintenance_schedule' => $setup->maintenance_schedule ?? null,
                                'depreciation_method' => $method,
                                'useful_life' => $life,
                                'salvage_value' => $salvageValue,
                                'depreciation_percentage' => $depreciationRate,
                                'depreciation_percentage_year' => $depreciationRate,
                                'total_depreciation' => 0,
                                'dep_type' => $organization->dep_type,
                                'current_value' => ($singlevalue * $batch->inventory_uom_qty),
                                'current_value_after_dep' => ($singlevalue * $batch->inventory_uom_qty),
                                'document_status' => 'approved',
                                'approval_level' => 1,
                                'revision_number' => 0,
                                'revision_date' => null,
                                'status' => 'active',
                            ];
                            $asset = FixedAssetRegistration::create($data);

                            FixedAssetSub::generateSubAssets(
                                $asset->id,
                                $asset->asset_code,
                                $batch->inventory_uom_qty,
                                $asset->current_value,
                                $asset->salvage_value
                            );


                            $mrn_asset->salvage_value = $salvageValue;
                            $mrn_asset->asset_code = $asset_code;
                            $mrn_asset->asset_id = $asset->id;
                            $mrn_asset->save();
                            $asset->batchupdateUniqueCodes($uniqueCodes, $batch, $offset);
                            $offset += $batch->inventory_uom_qty;
                        }
                    } else {
                        $asset_code = self::generateAssetCode($category_id);
                        $existingAsset = FixedAssetRegistration::where('asset_code', $asset_code)->first();

                        $data = [
                            'organization_id' => $user->organization_id,
                            'group_id' => $organization->group_id,
                            'company_id' => $organization->company_id,
                            'created_by' => $user->id,
                            'type' => get_class($user),
                            'book_id' => $glPostingBookId,
                            'document_number' => $mrn->document_number,
                            'document_date' => $mrn->document_date,
                            'mrn_detail_id' => $mrn_detail->id,
                            'mrn_header_id' => $mrn->id,
                            'asset_code' => $asset_code,
                            'asset_name' => $asset_name,
                            'brand_name' => $mrn_asset->brand_name,
                            'model_no' => $mrn_asset->model_no,
                            'procurement_type' => $mrn_asset->procurement_type,
                            'quantity' => $mrn_detail->accepted_inv_uom_qty,
                            'category_id' => $category_id,
                            'reference_doc_id' => $mrn->id,
                            'reference_series' => ConstantHelper::MRN_SERVICE_ALIAS,
                            'ledger_id' => $setup->ledger_id,
                            'ledger_group_id' => $setup->ledger_group_id,
                            'capitalize_date' => $capitalize_date,
                            'last_dep_date' => $capitalize_date,
                            'vendor_id' => $mrn->vendor_id,
                            'currency_id' => $mrn->vendor?->currency_id,
                            'sub_total' => $currentValue,
                            'tax' => $mrn_detail->tax_value,
                            'purchase_amount' => $currentValue + $mrn_detail->tax_value,
                            'supplier_invoice_date' => $mrn->supplier_invoice_date,
                            'book_date' => $mrn_detail->created_at ?? null,
                            'supplier_invoice_no' => $mrn->supplier_invoice_no,
                            'location_id' => $mrn->store_id ?? null,
                            'cost_center_id' => $mrn->cost_center_id ?? null,
                            'maintenance_schedule' => $setup->maintenance_schedule ?? null,
                            'depreciation_method' => $method,
                            'useful_life' => $life,
                            'salvage_value' => $salvageValue,
                            'depreciation_percentage' => $depreciationRate,
                            'depreciation_percentage_year' => $depreciationRate,
                            'total_depreciation' => 0,
                            'dep_type' => $organization->dep_type,
                            'current_value' => $currentValue,
                            'current_value_after_dep' => $currentValue,
                            'document_status' => 'approved',
                            'approval_level' => 1,
                            'revision_number' => 0,
                            'revision_date' => null,
                            'status' => 'active',
                        ];
                        $asset = FixedAssetRegistration::create($data);

                        $batches = $mrn_detail->batches;

                        FixedAssetSub::generateSubAssets(
                            $asset->id,
                            $asset->asset_code,
                            $asset->quantity,
                            $asset->current_value,
                            $asset->salvage_value
                        );
                        $mrn_asset->salvage_value = $salvageValue;
                        $mrn_asset->asset_code = $asset_code;
                        $mrn_asset->asset_id = $asset->id;
                        $mrn_asset->save();

                        $asset->updateUniqueCodes();
                    }
                }


                DB::commit();

                return [
                    'status' => true,
                    'message' => "Registration Added",
                    'data' => []
                ];
            } else {
                DB::commit();
                return [
                    'status' => true,
                    'message' => "MRN does not have any asset to register",
                    'data' => []
                ];
            }
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('MRN Asset Register Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return [
                'status' => false,
                'error' => $e->getMessage(),
                'message' => 'An error occurred during asset registration.',

            ];
        }
    }
}
