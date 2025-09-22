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

use App\Models\PO\PoHeader;
use App\Models\PO\PoDetail;

class DetailHistory extends Model
{
    protected $table = 'erp_exp_allocation_details_history';
    protected $fillable = [
        'source_id',
        'header_id',
        'item_id',
        'mrn_detail_id',
        'mrn_header_id',
        'item_code',
        'item_name',
        'hsn_id',
        'hsn_code',
        'uom_id',
        'uom_code',
        'store_id',
        'store_code',
        'sub_store_id',
        'sub_store_code',
        'order_qty',
        'accepted_qty',
        'inventory_uom_id',
        'inventory_uom_code',
        'inventory_uom_qty',
        'accepted_inv_uom_id',
        'accepted_inv_uom_code',
        'accepted_inv_uom_qty',
        'rate',
        'basic_value',
        'discount_percentage',
        'discount_amount',
        'header_discount_amount',
        'net_value',
        'tax_value',
        'taxable_amount',
        'item_exp_amount',
        'header_exp_amount',
        'total_item_amount',
        'remark'
    ];

    protected $appends = [
        'cgst_value',
        'sgst_value',
        'igst_value'
    ];

    public function header()
    {
        return $this->belongsTo(Header::class, 'header_id');
    }

    public function expenseHeader()
    {
        return $this->belongsTo(Header::class, 'header_id');
    }

    public function headerHistory()
    {
        return $this->belongsTo(HeaderHistory::class, 'header_history_id');
    }

    public function detail()
    {
        return $this->belongsTo(Detail::class, 'detail_id');
    }

    public function attributes()
    {
        return $this->hasMany(AttributeHistory::class, 'detail_history_id');
    }

    public function expenseTed()
    {
        return $this->hasMany(TedHistory::class, 'detail_history_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function getAssessmentAmountTotalAttribute()
    {
        return ($this->accepted_qty * $this->rate) - ($this->discount_amount - $this->header_discount_amount);
    }

    public function getAssessmentAmountItemAttribute()
    {
        return ($this->accepted_qty * $this->rate) - ($this->discount_amount);
    }

    // After item discount
    public function getAssessmentAmountHeaderAttribute()
    {
        return ($this->accepted_qty * $this->rate) - ($this->discount_amount);
    }

    public function getTotalItemValueAttribute()
    {
        return ($this->accepted_qty * $this->rate);
    }

    public function getTotalDiscValueAttribute()
    {
        return ($this->discount_amount + $this->header_discount_amount);
    }

    public function uom()
    {
        return $this->belongsTo(Unit::class, 'uom_id');
    }

    public function itemDiscount()
    {
        return $this->hasMany(TedHistory::class, 'detail_history_id')->where('ted_level', 'D')->where('ted_type', 'Discount');
    }

    /*Header Level Discount*/
    public function headerDiscount()
    {
        return $this->hasMany(TedHistory::class, 'detail_history_id')->where('ted_level', 'H')->where('ted_type', 'Discount');
    }

    public function taxes()
    {
        return $this->hasMany(TedHistory::class, 'detail_history_id')->where('ted_type', 'Tax');
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
            $existingAttribute = AttributeHistory::where('detail_history_id', $this->getAttribute('id'))->where('item_attribute_id', $attribute->id)->first();
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
