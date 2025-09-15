<?php

namespace App\Http\Controllers;

use App\Helpers\ConstantHelper;
use App\Helpers\Helper;
use App\Helpers\InventoryHelper;
use App\Helpers\RGR\Constants as RGRConstants;
use App\Helpers\TransactionReport\rfqReportHelper;
use App\Models\ErpRgrDefectType;
use App\Models\ErpRgrDefectTypeDetail;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ErpRgrDefectTypeController extends Controller
{
    //
    public function index(Request $request)
    {
        $selectedfyYear = Helper::getFinancialYear(Carbon::now());
        $selectedfyYear = Helper::getFinancialYear(Carbon::now()->format('Y-m-d'));
        $redirectUrl = route('rfq.index');
        $createRoute = route('rfq.create');
        $typeName = "Request For Quotation";
        $parentURL = request() -> segments()[0];
        $selectedfyYear = Helper::getFinancialYear(Carbon::now()->format('Y-m-d'));
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        $create_button = (isset($servicesBooks['services'])  && count($servicesBooks['services']) > 0 && isset($selectedfyYear['authorized']) && $selectedfyYear['authorized'] && !$selectedfyYear['lock_fy']) ? true : false;
        //Date Filters
        $dateRange = $request -> date_range ?? null;
        $data = ErpRgrDefectType::with('details')->get();
        return view('RgrDefect.create_edit', ['typeName' => $typeName, 'data' => $data ,'redirect_url' => $redirectUrl,'create_route' => $createRoute, 'create_button' => $create_button, 'filterArray' => rfqReportHelper::RFQ_FILTERS,
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'rows' => 'nullable|array',
            'rows.*.rgr_defect_id' => 'nullable|integer',
            'rows.*.category_id' => 'required_without:rows.*.rgr_defect_id|integer',
            'rows.*.severity' => 'required|string|max:255',
            'rows.*.reasons' => 'nullable|array',
            'rows.*.reasons.*.type' => 'required|string|max:255',
            'rows.*.reasons.*.detail_id' => 'nullable|integer',
            'deleted_ids' => 'nullable|array',
            'deleted_ids.*' => 'integer',
        ]);
        DB::beginTransaction();
        $user = Helper::getAuthenticatedUser() ?? request()->user();
        try {
            // 1️⃣ Handle deleted headers
            if (!empty($request->deleted_ids)) {
                ErpRgrDefectTypeDetail::whereIn('header_id', $request->deleted_ids)->delete();
                ErpRgrDefectType::whereIn('id', $request->deleted_ids)->delete();
            }

            // 2️⃣ Process each row
            if (!empty($request->rows)) {
                foreach ($request->rows as $row) {

                    // 2a️⃣ Existing header → update details only
                    if (!empty($row['rgr_defect_id'])) {
                        $defectType = ErpRgrDefectType::find($row['rgr_defect_id']);
                        if ($defectType) {
                            $submittedDetailIds = [];

                            // Delete any details not submitted
                            $defectType->details()
                                ->whereNotIn('id', $submittedDetailIds)
                                ->delete();

                            if (!empty($row['reasons'])) {
                                $defectType->details()->whereNotIn('id', array_filter(array_column($row['reasons'], 'detail_id')))->delete();
                                foreach ($row['reasons'] as $reason) {
                                    $defectType->details()->create([
                                        'type' => $reason['type']
                                    ]);
                                }
                            }

                        }

                    } else {
                        // 2b️⃣ New header → create header + details
                        $defectType = ErpRgrDefectType::create([
                            'group_id' => $user->group_id ?? null,
                            'company_id' => $user->company_id,
                            'organization_id' => $user->organization_id,
                            'category_id' => $row['category_id'],
                            'defect_severity' => $row['severity'],
                        ]);

                        // Create all submitted details
                        if (!empty($row['reasons'])) {
                            $details = array_map(fn($r) => ['type' => $r['type']], $row['reasons']);
                            $defectType->details()->createMany($details);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Defect Types saved successfully.'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    
}
