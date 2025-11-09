<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\ConstantHelper;
use App\Helpers\Helper;
use App\Helpers\BookHelper;
use App\Helpers\ItemHelper;
use App\Helpers\InventoryHelper;
use App\Http\Requests\RcaRequest;
use App\Models\ErpRcaHeader;
use App\Models\ErpRcaItem;
use App\Models\ErpRcaMedia;
use App\Models\ErpRcaItemAttribute;
use App\Models\Item;
use App\Models\ErpStore;
use Yajra\DataTables\DataTables;
use App\Helpers\ReManufacturing\RCA\Constants as RCAConstant;
use DB;
use Storage;
use Exception;

class RcaController extends Controller
{
    /**
     * Display a listing of repair orders.
     */
  public function index(Request $request)
    {
        if ($request->ajax()) {
            $rcaHeaders = ErpRcaHeader::with(['items', 'store'])->latest();

            if ($request->filled('status')) {
                $rcaHeaders->where('document_status', $request->status);
            }

            if ($request->filled('book_id')) {
                $rcaHeaders->where('book_id', $request->book_id);
            }

            if ($request->filled('location_id')) {
                $rcaHeaders->where('store_id', $request->location_id);
            }

            return DataTables::of($rcaHeaders)
                ->addIndexColumn()
                ->addColumn('book_name', fn($row) => $row->book_code ?? 'N/A')
                ->addColumn('location', fn($row) => $row->store->store_name ?? 'N/A')
                ->addColumn('document_status', function($row) {
                    $statusClass = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$row->document_status] ?? 'badge-light-secondary';
                    return "<span class='badge {$statusClass}'>" . ucfirst($row->document_status) . "</span>";
                })
                ->editColumn('document_date', fn($row) => $row->getFormattedDate('document_date') ?? 'N/A')
                ->addColumn('items', fn($row) => "<span class='badge rounded-pill badge-light-primary'>{$row->items->count()}</span>")
                ->addColumn('action', function ($row) {
                    $editRoute = route('rca.edit', $row->id);
                    return "
                        <div style='text-align:right;'>
                            <div class='dropdown' style='display:inline;'>
                                <button type='button' class='btn btn-sm dropdown-toggle hide-arrow py-0 p-0' data-bs-toggle='dropdown'>
                                    <i data-feather='more-vertical'></i>
                                </button>
                                <div class='dropdown-menu dropdown-menu-end'>
                                    <a class='dropdown-item' href='{$editRoute}'>
                                        <i data-feather='edit-3' class='me-50'></i>
                                        <span>View/Edit Detail</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    ";
                })
                ->rawColumns(['items', 'action', 'document_status'])
                ->make(true);
        }

        $books = Helper::getBookSeriesNew('RCA', request()->segment(1), true)->get();
        $locations = InventoryHelper::getAccessibleLocations();

        return view('rca.index', [
            'books' => $books,
            'locations' => $locations
        ]);
    }
    /**
     * Show the form for editing a repair order.
     */
  public function edit(Request $request, $id)
    {
        $parentUrl = request()->segments()[0];
        $servicesAliasParam = RCAConstant::SERVICE_ALIAS;
        $rcaHeader = ErpRcaHeader::with(['items.attributes','items.item','items.item.hsn','items.media'])->findOrFail($id);
        $books = Helper::getBookSeriesNew($servicesAliasParam, $parentUrl, true)->get();
        $locations = InventoryHelper::getAccessibleLocations();
        $creatorType = Helper::userCheck()['type'];
        $totalValue = 0;
        $buttons = Helper::actionButtonDisplay($rcaHeader->book_id,$rcaHeader->document_status,$rcaHeader->id,$totalValue,$rcaHeader->approval_level,$rcaHeader->created_by ?? 0,$creatorType,$rcaHeader->revision_number ?? 0);
        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$rcaHeader->document_status] ?? '';
        $revisionNumber = $rcaHeader->revision_number ?? 0;
        $docValue = 0;

        $approvalHistory = Helper::getApprovalHistory($rcaHeader->book_id,$rcaHeader->id,$revisionNumber,$docValue,$rcaHeader->created_by ?? 0);

        return view('rca.edit', [
            'rca' => $rcaHeader,
            'books' => $books,
            'locations' => $locations,
            'buttons' => $buttons,
            'docStatusClass' => $docStatusClass,
            'isEdit' => $buttons['submit'] ?? false,
            'serviceAlias' => $servicesAliasParam,
            'revisionNumber' => $revisionNumber,
            'approvalHistory' => $approvalHistory, 
        ]);
    }

  public function update(RcaRequest $request, $id)
  {
        DB::beginTransaction();
        try {
            $rcaHeader = ErpRcaHeader::findOrFail($id);
            // ---------- Update all header fields ----------
            $rcaHeader->book_id = $request->has('book_id') ? $request->input('book_id') : $rcaHeader->book_id;
            $rcaHeader->book_code = $request->has('book_code') ? $request->input('book_code') : $rcaHeader->book_code;
            $rcaHeader->store_id = $request->has('store_id') ? $request->input('store_id') : $rcaHeader->store_id;
            $rcaHeader->store_name = $request->has('store_name') ? $request->input('store_name') : $rcaHeader->store_name;
            $rcaHeader->rgr_id = $request->has('rgr_id') ? $request->input('rgr_id') : $rcaHeader->rgr_id;
            $rcaHeader->discrepancy_type = $request->has('discrepancy_type') ? $request->input('discrepancy_type') : $rcaHeader->discrepancy_type;
            $rcaHeader->customer_id = $request->has('customer_id') ? $request->input('customer_id') : $rcaHeader->customer_id;
            $rcaHeader->customer_phone_no = $request->has('customer_phone_no') ? $request->input('customer_phone_no') : $rcaHeader->customer_phone_no;
            $rcaHeader->unloading_date = $request->has('unloading_date') ? $request->input('unloading_date') : $rcaHeader->unloading_date;
            $rcaHeader->trip_no = $request->has('trip_no') ? $request->input('trip_no') : $rcaHeader->trip_no;
            $rcaHeader->vehicle_no = $request->has('vehicle_no') ? $request->input('vehicle_no') : $rcaHeader->vehicle_no;
            $rcaHeader->champ_name = $request->has('champ_name') ? $request->input('champ_name') : $rcaHeader->champ_name;
            $rcaHeader->items_count = $request->has('items_count') ? $request->input('items_count') : $rcaHeader->items_count;
            $rcaHeader->doc_number_type = $request->has('doc_number_type') ? $request->input('doc_number_type') : $rcaHeader->doc_number_type;
            $rcaHeader->doc_reset_pattern = $request->has('doc_reset_pattern') ? $request->input('doc_reset_pattern') : $rcaHeader->doc_reset_pattern;
            $rcaHeader->doc_prefix = $request->has('doc_prefix') ? $request->input('doc_prefix') : $rcaHeader->doc_prefix;
            $rcaHeader->doc_suffix = $request->has('doc_suffix') ? $request->input('doc_suffix') : $rcaHeader->doc_suffix;
            $rcaHeader->doc_no = $request->has('doc_no') ? $request->input('doc_no') : $rcaHeader->doc_no;
            $rcaHeader->document_number = $request->has('document_number') ? $request->input('document_number') : $rcaHeader->document_number;
            $rcaHeader->document_date = $request->has('document_date') ? $request->input('document_date') : $rcaHeader->document_date;
            $rcaHeader->fur_id = $request->has('fur_id') ? $request->input('fur_id') : $rcaHeader->fur_id;
            $rcaHeader->document_status = $request->has('document_status') ? $request->input('document_status') : $rcaHeader->document_status;
            $rcaHeader->revision_number = $request->has('revision_number') ? $request->input('revision_number') : $rcaHeader->revision_number;
            $rcaHeader->revision_date = $request->has('revision_date') ? $request->input('revision_date') : $rcaHeader->revision_date;
            $rcaHeader->approval_level = $request->has('approval_level') ? $request->input('approval_level') : $rcaHeader->approval_level;
            $rcaHeader->remarks = $request->has('remarks') ? $request->input('remarks') : $rcaHeader->remarks;
            $rcaHeader->save();

            // ---------- Update all items ----------
           if ($request->has('rca_items') && is_array($request->input('rca_items'))) {
            foreach ($request->input('rca_items') as $itemData) {
                $item = Item::with('uom')->find($itemData['item_id']);
                $inventoryUomQty = $item ? ItemHelper::convertToBaseUom(
                    $item->id,
                    $itemData['uom_id'],
                    $itemData['scheduled_qty'] ?? 0
                ) : 0;

                $rcaItem = isset($itemData['id']) ? ErpRcaItem::find($itemData['id']) : new ErpRcaItem;
                $rcaItem->rca_header_id = $rcaHeader->id;
                $rcaItem->item_id = $itemData['item_id'];
                $rcaItem->item_name = $itemData['item_name'];
                $rcaItem->item_code = $itemData['item_code'] ?? '';
                $rcaItem->item_uid = $itemData['item_uid'] ?? null;
                $rcaItem->uom_id = $itemData['uom_id'];
                $rcaItem->uom_code = $itemData['uom_code'];
                $rcaItem->scheduled_qty = $itemData['scheduled_qty'] ?? 0;
                $rcaItem->missing_qty = $itemData['missing_qty'] ?? 0;
                $rcaItem->inventory_uom_id = $item?->uom?->id;
                $rcaItem->inventory_uom_code = $item?->uom?->name;
                $rcaItem->inventory_uom_qty = $inventoryUomQty;
                $rcaItem->rgr_job_detail_id = $itemData['rgr_job_detail_id'] ?? null;
                $rcaItem->rgr_item_segregation_id = $itemData['rgr_item_segregation_id'] ?? null;
                $rcaItem->remark = $itemData['remark'] ?? null;
                $rcaItem->save();
                    // ---------- Update item attributes ----------
                   $itemAttributes = $itemData['rca_item_attributes'] ?? [];
                    foreach ($itemAttributes as $attr) {
                        $rcaAttr = isset($attr['id']) ? ErpRcaItemAttribute::find($attr['id']) : new ErpRcaItemAttribute;
                        $rcaAttr->rca_header_id = $rcaHeader->id;
                        $rcaAttr->rca_item_id = $rcaItem->id;
                        $rcaAttr->item_attribute_id = $attr['item_attribute_id'] ?? null;
                        $rcaAttr->item_code = $attr['item_code'] ?? $rcaItem->item_code;
                        $rcaAttr->attribute_name = $attr['attribute_name'] ?? null;
                        $rcaAttr->attr_name = $attr['attr_name'] ?? null;
                        $rcaAttr->attribute_value = $attr['attribute_value'] ?? null;
                        $rcaAttr->attr_value = $attr['attr_value'] ?? null;
                        $rcaAttr->save();
                    }

                    // ---------- Update media ----------
                    if (isset($itemData['media']) && is_array($itemData['media'])) {
                        foreach ($itemData['media'] as $file) {
                            $rcaItem->uploadDocuments($file, 'rca_item');
                            
                        }
                    }
                }
            }

            // ---------- Update header media ----------
            if ($request->hasFile('attachment')) {
                $rcaHeader->uploadDocuments($request->file('attachment'), 'rca_header', true);
            }

            // ---------- Auto-approve if submitted ----------
            if ($request->input('document_status') == ConstantHelper::SUBMITTED) {
                $approveDocument = Helper::approveDocument(
                    $rcaHeader->book_id,
                    $rcaHeader->id,
                    $rcaHeader->revision_number ?? 0,
                    $rcaHeader->remarks,
                    $request->file('attachment'),
                    $rcaHeader->approval_level,
                    'submit',
                    0,
                    get_class($rcaHeader)
                );
                if ($approveDocument['message']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => $approveDocument['message'],
                        'error' => "",
                    ], 422);
                }
                $rcaHeader->document_status = $approveDocument['approvalStatus'] ?? $rcaHeader->document_status;
                $rcaHeader->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'RCA updated successfully',
                'data' => $rcaHeader,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error occurred while updating the RCA',
                'error' => $e->getMessage(),
            ], 500);
        }
}

public function updateRemark(Request $request)
{
    $request->validate([
        'rca_item_id' => 'required|exists:erp_rca_items,id',
        'remark' => 'nullable|string',
        'media.*' => 'nullable|file|mimes:jpg,jpeg,png',
        'deletedMediaIds' => 'nullable|string', 
    ]);

    $rcaItem = ErpRcaItem::findOrFail($request->rca_item_id);

    $rcaItem->remark = $request->remark;
    $rcaItem->save();
    if ($request->filled('deletedMediaIds')) {
        $deletedIds = array_map('intval', explode(',', $request->deletedMediaIds));
        $mediaToDelete = ErpRcaMedia::whereIn('id', $deletedIds)
            ->where('model_type', ErpRcaItem::class) 
            ->where('model_id', $rcaItem->id)
            ->get();

        foreach ($mediaToDelete as $media) {
            if (Storage::exists($media->file_name)) {
                Storage::delete($media->file_name);
            }
            $media->delete();
        }
    }
    // Upload new media
    if ($request->hasFile('media')) {
        $files = $request->file('media');
        foreach ($request->file('media') as $file) {
            $rcaItem->uploadDocuments($file, 'rca_item', false); 
        }
    }

    return response()->json([
        'message' => 'Remark updated successfully',
        'rca_item_id' => $rcaItem->id
    ]);
}

public function revokeDocument(Request $request)
{
    DB::beginTransaction();
    try {
        $rcaHeader = ErpRcaHeader::find($request->id);

        if (isset($rcaHeader)) {
            $revoke = Helper::approveDocument($rcaHeader->book_id,$rcaHeader->id,$rcaHeader->revision_number,'',[],0,ConstantHelper::REVOKE,0, get_class($rcaHeader));
            if ($revoke['message']) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $revoke['message'],
                ]);
            } else {
                $rcaHeader->document_status = $revoke['approvalStatus'];
                $rcaHeader->save();
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'RCA revoked successfully',
                ]);
            }
        } else {
            DB::rollBack();
            throw new Exception("No RCA found");
        }
    } catch (Exception $ex) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => $ex->getMessage(),
        ], 500);
    }
}
}
