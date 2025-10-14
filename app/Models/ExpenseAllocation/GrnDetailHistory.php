<?php
namespace App\Models\ExpenseAllocation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Helpers\ConstantHelper;

use App\Models\Hsn;
use App\Models\Item;
use App\Models\Unit;
use App\Models\TaxDetail;
use App\Models\CostCenter;
use App\Models\ErpAttribute;
use App\Models\ItemAttribute;
use App\Models\ErpItemAttribute;

use App\Models\MrnDetail;
use App\Models\MrnHeader;
use App\Models\Vendor;

class GrnDetailHistory extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'erp_exp_alc_grn_details_history';
    protected $fillable = [
        'source_id',
        'header_id',
        'grn_header_id',
        'grn_detail_id',
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
        'weight',
        'volume',
        'value',
        'grn_value',
        'allocated_cost',
        'landed_cost'
    ];

    public function header()
    {
        return $this->belongsTo(Header::class);
    }

    public function mrnHeader()
    {
        return $this->belongsTo(MrnHeader::class);
    }

    public function mrnDetail()
    {
        return $this->belongsTo(MrnDetail::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function headerHistory()
    {
        return $this->belongsTo(HeaderHistory::class, 'header_history_id');
    }

    public function attributes()
    {
        return $this->hasMany(GrnAttributeHistory::class, 'grn_detail_id');
    }

    public function allocations()
    {
        return $this->hasMany(AllocationHistory::class, 'grn_detail_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function hsn()
    {
        return $this->belongsTo(Hsn::class, 'hsn_id');
    }

    public function uom()
    {
        return $this->belongsTo(Unit::class, 'uom_id');
    }

    public function inventoryUom()
    {
        return $this->belongsTo(Unit::class, 'inventory_uom_id');
    }

    public function item_attributes_array()
    {
        $itemId = $this->getAttribute('item_id');
        if (!$itemId) {
            return collect([]);
        }
        $itemAttributes = ItemAttribute::where('item_id', $itemId)->get();
        $processedData = [];
        $mappingAttributes = GrnAttributeHistory::where('detail_id', $this->getAttribute('id'))
            ->select(['item_attribute_id as attribute_id', 'attr_value as attribute_value_id'])
            ->get()
            ->toArray();
        foreach ($itemAttributes as $attribute) {
            $attributeIds = is_array($attribute->attribute_id) ? $attribute->attribute_id : [$attribute->attribute_id];
            $attribute->group_name = $attribute->group?->name;
            $valuesData = [];
            foreach ($attributeIds as $attributeValueId) {
                $attributeValueData = ErpAttribute::where('id', $attributeValueId)
                    ->where('status', 'active')
                    ->select('id', 'value')
                    ->first();
                if ($attributeValueData) {
                    $isSelected = collect($mappingAttributes)->contains(function ($itemAttr) use ($attribute, $attributeValueData) {
                        return $itemAttr['attribute_id'] == $attribute->id &&
                            $itemAttr['attribute_value_id'] == $attributeValueData->id;
                    });
                    $attributeValueData->selected = $isSelected;
                    $valuesData[] = $attributeValueData;
                }
            }
            $processedData[] = [
                'id' => $attribute->id,
                'group_name' => $attribute->group_name,
                'values_data' => $valuesData,
                'attribute_group_id' => $attribute->attribute_group_id,
            ];
        }
        return collect($processedData);
    }
}
