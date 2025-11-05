<?php

namespace App\Helpers;

use stdClass;
use Exception;
use Carbon\Carbon;
use App\Models\FixedAssetRegistration;
use App\Models\FixedAssetSub;
use App\Models\PbHeader;
use App\Models\MrnHeader;
use App\Models\MrnAssetDetail;
use App\Models\Book;
use App\Models\OrganizationBookParameter;
use App\Models\FixedAssetSetup;
use App\Models\MrnDetail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

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

    public static function mrnExpenseAssetRegister($mrn_id, $alias): array
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

                    if (!empty($exitingReg)) 
                    {
                        
                         Log::info('mrn register', [
                                'mrn_detail_id' => $mrn_detail->id,
                                'mrn_header_id' => $mrn->id,
                                'message' =>  'existing fixed asset id in db'
                            ]);
                        $expensedata = DB::table('erp_exp_alc_grn_details')->where('grn_header_id',$mrn->id)->where('grn_detail_id',$mrn_detail->id)->first();
                        if(!empty($expensedata))
                        {
                            $exitingReg->expense = $expensedata->allocated_cost;
                            $exitingReg->purchase_amount = $expensedata->allocated_cost + $exitingReg->purchase_amount;
                            $exitingReg->save();
                             Log::info('mrn register', [
                                'mrn_detail_id' => $mrn_detail->id,
                                'mrn_header_id' => $mrn->id,
                                'message' =>  'expense save in db'
                            ]);
                        }
                        Log::info('mrn register', [
                                'mrn_detail_id' => $mrn_detail->id,
                                'mrn_header_id' => $mrn->id,
                                'message' =>  'expense data is not exist in db'
                            ]);
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
