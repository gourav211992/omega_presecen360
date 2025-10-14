<?php

namespace App\Models\ExpenseAllocation;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ConstantHelper;

use App\Models\Item;
use App\Models\Unit;
use App\Models\TaxDetail;
use App\Models\CostCenter;
use App\Models\ErpAttribute;
use App\Models\ItemAttribute;
use App\Models\ErpItemAttribute;

use App\Models\PoItem;
use App\Models\PurchaseOrder;
use App\Models\Vendor;

class PoDetailHistory extends Model
{
    protected $table = 'erp_exp_alc_po_details_history';
    protected $fillable = [
        'source_id',
        'header_id',
        'po_header_id',
        'po_detail_id',
        'item_id',
        'item_code',
        'item_name',
        'hsn_id',
        'hsn_code',
        'uom_id',
        'uom_code',
        'vendor_id',
        'vendor_code',
        'vendor_name',
        'currency_id',
        'currency_code',
        'org_currency_id',
        'org_currency_code',
        'exchange_rate',
        'po_qty',
        'receipt_qty',
        'inventory_uom_id',
        'inventory_uom_code',
        'inventory_uom_qty',
        'receipt_inv_uom_id',
        'receipt_inv_uom_code',
        'receipt_inv_uom_qty',
        'rate',
        'value',
        'po_value',
        'allocation_type_id',
        'allocation_type',
        'remark'
    ];

    public function header()
    {
        return $this->belongsTo(Header::class, 'header_id');
    }

    public function poHeader()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_header_id');
    }

    public function poDetail()
    {
        return $this->belongsTo(PoItem::class, 'po_detail_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function headerHistory()
    {
        return $this->belongsTo(HeaderHistory::class, 'header_history_id');
    }

    public function detail()
    {
        return $this->belongsTo(PoDetail::class, 'po-detail_id');
    }

    public function allocations()
    {
        return $this->hasMany(AllocationHistory::class, 'po_detail_id');
    }

    public function attributes()
    {
        return $this->hasMany(PoAttributeHistory::class, 'po_detail_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function uom()
    {
        return $this->belongsTo(Unit::class, 'uom_id');
    }

    public function item_attributes_array()
    {
        $itemId = $this->getAttribute('item_id');
        if (isset($itemId)) {
            $itemAttributes = ErpItemAttribute::where('item_id', $this->item_id)->get();
        } else {
            $itemAttributes = [];
        }
        $processedData = [];
        foreach ($itemAttributes as $attribute) {
            $existingAttribute = PoAttributeHistory::where('detail_history_id', $this->getAttribute('id'))->where('item_attribute_id', $attribute->id)->first();
            if (!isset($existingAttribute)) {
                continue;
            }
            $attributesArray = array();
            $attribute_ids = [];
            if ($attribute->all_checked) {
                $attribute_ids = ErpAttribute::where('attribute_group_id', $attribute->attribute_group_id)->get()->pluck('id')->toArray();
            } else {
                $attribute_ids = $attribute->attribute_id ? json_decode($attribute->attribute_id) : [];
            }
            $attribute->group_name = $attribute->group?->name;
            $attribute->short_name = $attribute->group?->short_name;
            foreach ($attribute_ids as $attributeValue) {
                $attributeValueData = ErpAttribute::where('id', $attributeValue)->select('id', 'value')->where('status', 'active')->first();
                if (isset($attributeValueData)) {
                    $isSelected = AttributeHistory::where('detail_history_id', $this->getAttribute('id'))->where('item_attribute_id', $attribute->id)->where('attr_value', $attributeValueData->id)->first();
                    $attributeValueData->selected = $isSelected ? true : false;
                    array_push($attributesArray, $attributeValueData);
                }
            }
            $attribute->values_data = $attributesArray;
            $attribute = $attribute->only(['id', 'group_name', 'short_name', 'values_data', 'attribute_group_id']);
            array_push($processedData, ['id' => $attribute['id'], 'group_name' => $attribute['group_name'], 'values_data' => $attributesArray, 'attribute_group_id' => $attribute['attribute_group_id'], 'short_name' => $attribute['short_name']]);
        }
        $processedData = collect($processedData);
        return $processedData;
    }
}
