<?php

namespace App\Repositories;

use App\Models\StockLedger;
use App\Helpers\CommonHelper;

class StockLedgerRepository
{
    public function getStocks($organizationId, $storeId, $subStoreId, $itemId = null)
    {
        $data = StockLedger::with([
                'item:id,item_name,item_code,uom_id',
                'location:id,store_name,store_code',
                'item.uom:id,name',
                'store:id,name'
            ])
            ->where('organization_id', $organizationId)
            ->where('store_id', $storeId)
            ->when(!empty($subStoreId), function ($query) use ($subStoreId) {
                $query->whereIn('sub_store_id', $subStoreId);
            })
            ->when($itemId, function($query) use($itemId){
                $query->where('item_id', $itemId);
            })
            ->withDefaultGroupCompanyOrg()
            ->whereNull('utilized_id')
            ->where('transaction_type', CommonHelper::RECEIPT)
            ->select(
                'id',
                'group_id',
                'company_id',
                'organization_id',
                'store_id',
                'sub_store_id',
                'item_id',
                'item_attributes',
            )
            ->selectRaw('SUM(CASE WHEN document_status IN (?, ?, ?) THEN (receipt_qty - reserved_qty) ELSE 0 END) as confirmed_stock', ['approved', 'approval_not_required', 'posted'])
            ->selectRaw('SUM(CASE WHEN document_status NOT IN (?, ?, ?) THEN receipt_qty ELSE 0 END) as unconfirmed_stock', ['approved', 'approval_not_required', 'posted'])
            ->selectRaw('SUM(CASE WHEN document_status IN (?, ?, ?) THEN putaway_pending_qty ELSE 0 END) as putaway_pending_qty', ['approved', 'approval_not_required', 'posted'])
            ->selectRaw('SUM(CASE WHEN document_status IN (?, ?, ?) THEN reserved_qty ELSE 0 END) as reserved_qty', ['approved', 'approval_not_required', 'posted'])
            ->selectRaw('SUM(CASE WHEN document_status IN (?, ?, ?) THEN org_currency_cost ELSE 0 END) as confirmed_stock_value', ['approved', 'approval_not_required', 'posted'])
            ->selectRaw('SUM(CASE WHEN document_status NOT IN (?, ?, ?) THEN org_currency_cost ELSE 0 END) as unconfirmed_stock_value', ['approved', 'approval_not_required', 'posted'])
            ->groupBy(['item_id'])
            ->paginate(CommonHelper::PAGE_LENGTH_50);

        $data->getCollection()->transform(function ($item) {
            $item->item_attributes = json_decode($item->item_attributes, true);
            return $item;
        });

        return $data;

    }
}
