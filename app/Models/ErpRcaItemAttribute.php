<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpRcaItemAttribute extends Model
{
    use HasFactory;

    protected $table = 'erp_rca_item_attributes';

    protected $fillable = [
        'rca_header_id',
        'rca_item_id',
        'item_attribute_id',
        'item_code',
        'attribute_name',
        'attr_name',
        'attribute_value',
        'attr_value',
    ];

    public function header()
    {
        return $this->belongsTo(ErpRcaHeader::class, 'rca_header_id');
    }

    public function item()
    {
        return $this->belongsTo(ErpRcaItem::class, 'rca_item_id');
    }

    public function attributeGroup()
    {
        return $this->belongsTo(AttributeGroup::class, 'attr_name', 'id');
    }


    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attr_value', 'id');
    }

    public function itemAttributeMaster()
    {
        return $this->belongsTo(ItemAttribute::class, 'item_attribute_id', 'id');
    }
}
