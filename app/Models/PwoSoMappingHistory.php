<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PwoSoMappingHistory extends Model
{
    use HasFactory;
     protected $table = 'erp_pwo_so_mapping_history';
    protected $fillable = [
        'mo_id',
        'so_id',
        'so_item_id',
        'bom_id',
        'production_route_id',
        'item_id',
        'created_by',
        'pwo_id',
        'item_code',
        'qty',
        'attributes',
        'uom_id',
        'uom_code',
        'inventory_uom_id',
        'inventory_uom_code',
        'inventory_uom_qty',
        'mo_product_qty',
        'store_id',
        'main_so_item',
        'jo_qty'
    ];

    protected $appends = [
        'pslip_balance_qty',
        'customer_code',
    ];
    protected $casts = [
        'attributes' => 'array'
    ];
    public function item()
    {
        return $this->belongsTo(Item::class,'item_id');
    }
      public function attributes()
    {
        return $this->hasMany(ErpSoItemAttributeHistory::class,'so_item_id','so_item_id');
    }
      public function item_attributes_array()
    {
        $itemId = $this->getAttribute('item_id');
        if (!$itemId) {
            return collect([]);
        }
        $itemAttributes = ItemAttribute::where('item_id', $itemId)->get();
        $processedData = [];
        $mappingAttributes = $this->getAttribute('attributes');
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
                        return $itemAttr['item_attribute_id'] == $attribute->id &&
                            $itemAttr['attribute_id'] == $attributeValueData->id;
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

        public function so()
    {
        return $this->belongsTo(ErpSaleOrder::class,'so_id');
    }

        public function store()
    {
        return $this->belongsTo(ErpStore::class, 'store_id');
    }

       public function uom()
    {
        return $this->belongsTo(Unit::class, 'uom_id');
    }

      public function pwoBomMapping()
    {
        return $this->hasMany(PwoBomMappingHistory::class,'pwo_mapping_id');
    }
}
