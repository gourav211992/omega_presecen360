<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\ConstantHelper;
use App\Helpers\Helper;
use App\Helpers\BookHelper;
use App\Helpers\ItemHelper;
use App\Helpers\InventoryHelper;
use App\Http\Requests\RepairOrderRequest;
use App\Models\ErpRepairOrder;
use App\Models\ErpRepItem;
use App\Models\ErpRepItemAttribute;
use App\Helpers\ReManufacturing\RepairOrder\Constants as REPConstant;
use App\Models\Item;
use App\Models\ErpStore;
use Yajra\DataTables\DataTables;
use DB;
use Exception;

class RepairOrderController extends Controller
{
    /**
     * Display a listing of repair orders.
     */
   public function index(Request $request)
    {
        if ($request->ajax()) {
            $repairOrders = ErpRepairOrder::with(['items', 'store'])->latest();

            if ($request->filled('status')) {
                $repairOrders->where('document_status', $request->status);
            }

            if ($request->filled('book_id')) {
                $repairOrders->where('book_id', $request->book_id);
            }

            if ($request->filled('location_id')) {
                $repairOrders->where('store_id', $request->location_id);
            }

            return DataTables::of($repairOrders)
                ->addIndexColumn()
                ->addColumn('book_name', fn($row) => $row->book_code ?? 'N/A')
                ->addColumn('location', fn($row) => $row->store->store_name ?? 'N/A')
                ->addColumn('type', fn($row) => $row->type ? ucwords(str_replace('_', ' ', $row->type)) : 'N/A')
                ->addColumn('document_status', function($row) {
                    $statusClass = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$row->document_status] ?? 'badge-light-secondary';
                    return "<span class='badge {$statusClass}'>" . ucfirst($row->document_status) . "</span>";
                })
                ->editColumn('document_date', fn($row) => $row->getFormattedDate('document_date') ?? 'N/A')
                ->addColumn('items', fn($row) => "<span class='badge rounded-pill badge-light-primary'>{$row->items->count()}</span>")
                ->addColumn('action', function ($row) {
                    $editRoute = route('repair-order.edit', $row->id);

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

        $parentUrl = request()->segments()[0];
        $servicesAliasParam =  RepConstant::SERVICE_ALIAS; 
        $books = Helper::getBookSeriesNew($servicesAliasParam, $parentUrl, true)->get();
        $locations = InventoryHelper::getAccessibleLocations();

        return view('repair-orders.index', [
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
        $servicesAliasParam = RepConstant::SERVICE_ALIAS;
        $repairOrder = ErpRepairOrder::with(['items.attributes','items.item','items.item.hsn','items.rgrSegregations','items.rgrSegregations.media'])->findOrFail($id);
        $books = Helper::getBookSeriesNew($servicesAliasParam, $parentUrl, true)->get();
        $locations = InventoryHelper::getAccessibleLocations();
        $creatorType = Helper::userCheck()['type'];
        $totalValue = 0;
        $buttons = Helper::actionButtonDisplay( $repairOrder->book_id, $repairOrder->document_status, $repairOrder->id, $totalValue, $repairOrder->approval_level, $repairOrder->created_by ?? 0,$creatorType,$repairOrder->revision_number ?? 0);
        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$repairOrder->document_status] ?? '';
        $repairActions = ConstantHelper::REPAIR_ACTION;
        $revisionNumber = $repairOrder->revision_number ?? 0;
        $docValue = 0;
        $approvalHistory = Helper::getApprovalHistory($repairOrder->book_id,$repairOrder->id,$revisionNumber,$docValue,$repairOrder->created_by ?? 0);

        return view('repair-orders.edit', [
            'repairOrder' => $repairOrder,
            'books' => $books,
            'locations' => $locations,
            'buttons' => $buttons,
            'docStatusClass' => $docStatusClass,
            'repairActions' => $repairActions,
            'isEdit' => $buttons['submit'] ?? false,
            'serviceAlias' => $servicesAliasParam,
            'revisionNumber' => $revisionNumber,
            'approvalHistory' => $approvalHistory, 
        ]);
    }

public function update(RepairOrderRequest $request, $id)
{
    DB::beginTransaction();
    try {
        $repairOrder = ErpRepairOrder::findOrFail($id);

        // ---------- Update header ----------
        $repairOrder->document_status = $request->input('document_status');
        $repairOrder->remarks = $request->input('remarks');
        $repairOrder->store_id = $request->input('store_id');
        $repairOrder->store_name = $request->input('store_name');
        $repairOrder->book_id = $request->input('book_id');
        $repairOrder->book_code = $request->input('book_code');
        $repairOrder->type = $request->input('type');
        $repairOrder->save();

        // ---------- Update repair items ----------
        if ($request->has('repair_items') && is_array($request->input('repair_items'))) {
            foreach ($request->input('repair_items') as $itemData) {
                $item = Item::with('uom')->find($itemData['item_id']);
                $inventoryUomQty = $item ? ItemHelper::convertToBaseUom($item->id, $itemData['uom_id'], $itemData['qty']) : 0;

                $repItem = isset($itemData['id']) ? ErpRepItem::find($itemData['id']) : new ErpRepItem;
                $repItem->repair_order_id = $repairOrder->id;

                $repItem->item_id = $itemData['item_id'];
                $repItem->item_name = $itemData['item_name'];
                $repItem->item_code = $itemData['item_code'] ?? '';
                $repItem->uom_id = $itemData['uom_id'];
                $repItem->uom_code = $itemData['uom_code'];
                $repItem->qty = $itemData['qty'];
                $repItem->inventory_uom_id = $item?->uom?->id;
                $repItem->inventory_uom_code = $item?->uom?->name;
                $repItem->inventory_uom_qty = $inventoryUomQty;
                $repItem->repair_remarks = $itemData['repair_remarks'] ?? '';
                $repItem->save();

                // ---------- Update item attributes ----------
                $itemAttributes = $itemData['rep_item_attributes'] ?? [];
                foreach ($itemAttributes as $attr) {
                    $repAttr = isset($attr['id']) ? ErpRepItemAttribute::find($attr['id']) : new ErpRepItemAttribute;
                    $repAttr->repair_order_id = $repairOrder->id;
                    $repAttr->rep_item_id = $repItem->id;
                    $repAttr->item_attribute_id = $attr['item_attribute_id'] ?? null;
                    $repAttr->attribute_name = $attr['attribute_name'] ?? null;
                    $repAttr->attr_name = $attr['attr_name'] ?? null;
                    $repAttr->attribute_value = $attr['attribute_value'] ?? null;
                    $repAttr->attr_value = $attr['attr_value'] ?? null;
                    $repAttr->save();
                }

                // ---------- Update defect logs ----------
                $defects = $itemData['defects'] ?? [];
                foreach ($defects as $defect) {
                    $repDefect = isset($defect['id']) ? ErpRepItemDefectLog::find($defect['id']) : new ErpRepItemDefectLog;
                    $repDefect->repair_order_id = $repairOrder->id;
                    $repDefect->rep_item_id = $repItem->id;
                    $repDefect->defect_severity = $defect['defect_severity'] ?? null;
                    $repDefect->defect_type = $defect['defect_type'] ?? null;
                    $repDefect->damage_nature = $defect['damage_nature'] ?? null;
                    $repDefect->remarks = $defect['remarks'] ?? '';
                    $repDefect->save();

                    // ---------- Save defect media ----------
                    if (isset($defect['media']) && is_array($defect['media'])) {
                        foreach ($defect['media'] as $file) {
                            $repDefect->uploadDocuments($file, 'defect_log');
                        }
                    }
                }

                // ---------- Save repair item attachments ----------
                if (isset($itemData['media']) && is_array($itemData['media'])) {
                    foreach ($itemData['media'] as $file) {
                        $repItem->uploadDocuments($file, 'repair_item');
                    }
                }
            }
        }

        // ---------- Save repair order attachments ----------
        if ($request->hasFile('attachment')) {
            $repairOrder->uploadDocuments($request->file('attachment'), 'repair_order', true);
        }
        // ---------- Auto-approve if submitted ----------
            $approveDocument = Helper::approveDocument(
                $repairOrder->book_id,
                $repairOrder->id,
                $repairOrder->revision_number ?? 0,
                $repairOrder->remarks,
                $request->file('attachment'),
                $repairOrder->approval_level,
                'submit',
                0,
                get_class($repairOrder)
            );

            if ($approveDocument['message']) {
                DB::rollBack();
                return response()->json([
                    'message' => $approveDocument['message'],
                    'error' => "",
                ], 422);
            }

           
        $repairOrder->document_status = $approveDocument['approvalStatus'] ?? $repairOrder->document_status;
        
        $repairOrder->save(); 

        DB::commit();

        return response()->json([
            'message' => 'Repair order updated successfully',
            'data' => $repairOrder,
        ]);

    } catch (Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Error occurred while updating the repair order',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function revokeDocument(Request $request)
    {
        DB::beginTransaction();
        try {
            $repairOrder = ErpRepairOrder::find($request->id);

            if (isset($repairOrder)) {
                $revoke = Helper::approveDocument($repairOrder->book_id,$repairOrder->id,$repairOrder->revision_number,'',[], 0, ConstantHelper::REVOKE,0, get_class($repairOrder));
                if ($revoke['message']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => $revoke['message'],
                    ]);
                } else {
                    $repairOrder->document_status = $revoke['approvalStatus'];
                    $repairOrder->save();
                    DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Revoked successfully',
                    ]);
                }
            } else {
                DB::rollBack();
                throw new ApiGenericException("No Repair Order found");
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
