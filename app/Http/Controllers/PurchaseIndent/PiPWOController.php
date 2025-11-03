<?php

namespace App\Http\Controllers\PurchaseIndent;

use DB;
use App\Helpers\Helper;
use App\Models\ErpSoItem;
use App\Models\ErpPwoItem;
use App\Models\PiSoMapping;
use App\Models\PwoSoMapping;
use Illuminate\Http\Request;
use App\Models\PwoBomMapping;
use App\Services\PI\PiService;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Helpers\ServiceParametersHelper;

class PiPWOController extends Controller
{
    # Get So Item List
    public function getPwo(Request $request)
    {
        $documentDate = $request->document_date ?? null;
        $pwoBookId = $request->series_id ?? null;
        $pwoDocNumber = $request->document_number ?? null;
        $soDocNumber = $request->so_doc_number ?? null;
        $soBookId = $request->so_book_id ?? null;
        $storeId = $request->store_id ?? null;
        $headerBookId = $request->header_book_id ?? null;
        $itemSearch = $request->item_search ?? null;
        $applicableBookIds = ServiceParametersHelper::getBookCodesForReferenceFromParam($headerBookId);
        $selectedPwoSoMappingIds = json_decode($request->selected_pi_ids, true) ?? [];

        $pwoItems = PwoSoMapping::whereHas('pwo', function ($subQuery) use ($applicableBookIds, $pwoBookId, $pwoDocNumber) {
            $subQuery->withDefaultGroupCompanyOrg()
                ->whereIn('book_id', $applicableBookIds)
                ->whereIn('document_status', [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED])
                ->when($pwoBookId, function ($bookQuery) use ($pwoBookId) {
                    $bookQuery->where('book_id', $pwoBookId);
                })
                ->when($pwoDocNumber, function ($bookQuery) use ($pwoDocNumber) {
                    $normalized = preg_replace('/[^a-zA-Z0-9]+/', ' ', $pwoDocNumber);
                    $keywords = preg_split('/\s+/', trim($normalized));
                    $bookQuery->where(function ($query) use ($keywords) {
                        foreach ($keywords as $word) {
                            $query->where(function ($subQuery) use ($word) {
                                $subQuery->where('book_code', 'LIKE', "%{$word}%")->orWhere('document_number', 'LIKE', "%{$word}%");
                            });
                        }
                    });
                });
        })
            ->whereHas('bom', function ($query) {
                $query->whereIn('document_status', [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED]);
            })
            ->when($storeId, function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->when($soBookId, function ($q) use ($soBookId) {
                $q->whereHas('so', function ($soQ) use ($soBookId) {
                    $soQ->where('book_id', $soBookId);
                });
            })
            ->when($soDocNumber, function ($q) use ($soDocNumber) {
                $q->whereHas('so', function ($soQ) use ($soDocNumber) {
                    $normalized = preg_replace('/[^a-zA-Z0-9]+/', ' ', $soDocNumber);
                    $keywords = preg_split('/\s+/', trim($normalized));
                    $soQ->where(function ($query) use ($keywords) {
                        foreach ($keywords as $word) {
                            $query->where(function ($subQuery) use ($word) {
                                $subQuery->where('book_code', 'LIKE', "%{$word}%")->orWhere('document_number', 'LIKE', "%{$word}%");
                            });
                        }
                    });
                });
            })
            ->when(count($selectedPwoSoMappingIds), function ($q) use ($selectedPwoSoMappingIds) {
                $q->whereNotIn('id', $selectedPwoSoMappingIds);
            })
            ->when($itemSearch, function ($q) use ($itemSearch) {
                $q->whereHas('item', function ($q2) use ($itemSearch) {
                    $q2->where('item_name', 'like', "%$itemSearch%")
                        ->orWhere('item_code', 'like', "%$itemSearch%");
                });
            })
            ->whereColumn('qty', '>', 'jo_qty');

        $pwoItems = $pwoItems->orderBy('id', 'DESC')->get();

        $html = view('procurement.pi.partials.pwo-item-list', ['pwoItems' => $pwoItems])->render();
        return response()->json(['data' => ['pis' => $html], 'status' => 200, 'message' => "fetched!"]);
    }

    public function processPwoItem(Request $request)
    {
        $ids = array_values(array_unique(json_decode($request->ids, true) ?? []));
        $soTrackingRequired = strtolower($request->so_tracking_required) == 'yes';

        if (empty($ids)) {
            return response()->json([
                'status' => 422,
                'message' => 'No PWO Mapping IDs provided.',
            ]);
        }

        try {
            $groupedDatas = PwoBomMapping::selectRaw('id, pwo_id, so_id, item_id, item_code, uom_id, attributes, SUM(qty) as total_qty')
                ->with(['item', 'pwo'])
                ->whereIn('pwo_mapping_id', $ids)
                ->groupBy('pwo_mapping_id', 'so_id', 'item_id', 'item_code', 'uom_id', 'attributes')
                ->get();

            $html = view('procurement.pi.partials.pwo-process-data', compact('groupedDatas', 'soTrackingRequired'))->render();

            return response()->json([
                'data' => ['pos' => $html],
                'status' => 200,
                'message' => 'Fetched successfully!',
            ]);
        } catch (\Exception $ex) {

            return response()->json([
                'data' => ['pos' => ''],
                'status' => 422,
                'message' => $ex->getMessage() . ' at line ' . $ex->getLine() . ' in ' . $ex->getFile(),
            ]);
        }
    }

    public function processPwoItemSubmit(Request $request)
    {
        try {
            $storeId = $request->input('store_id');
            $pwoBomMappingIds = $request->input('pwo_bom_mapping_ids', []);
            $soTrackingRequired = strtolower($request->so_tracking_required) == 'yes';

            if (empty($pwoBomMappingIds) || !is_array($pwoBomMappingIds)) {
                return response()->json([
                    'data' => ['pos' => ''],
                    'status' => 422,
                    'message' => 'No PWO BOM Mapping IDs provided.',
                ]);
            }

            $pwoBomMappings = PwoBomMapping::with(['pwo', 'item.itemAttributes.attributeGroup', 'uom', 'so'])
                ->whereIn('id', $pwoBomMappingIds)
                ->get()
                ->map(function ($item) {
                    $item->attributes = $item->item_attributes_array();
                    return $item;
                });

            if ($pwoBomMappings->isEmpty()) {
                return response()->json([
                    'data' => ['pos' => ''],
                    'status' => 404,
                    'message' => 'No PWO BOM Mapping records found for the given IDs.',
                ]);
            }

            $rowCount = intval($request->input('rowCount', 0)) + 1;

            $html = view('procurement.pi.partials.item-row-pwo', [
                'is_pull'  => true,
                'storeId'  => $storeId,
                'rowCount' => $rowCount,
                'pwoBomMappings' => $pwoBomMappings,
                'soTrackingRequired' => $soTrackingRequired,
            ])->render();

            return response()->json([
                'data'    => ['pos' => $html],
                'status'  => 200,
                'message' => "Fetched successfully!",
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'data' => ['pos' => ''],
                'status' => 500,
                'message' => $ex->getMessage() . ' at line ' . $ex->getLine() . ' in ' . $ex->getFile(),
            ]);
        }
    }
}
