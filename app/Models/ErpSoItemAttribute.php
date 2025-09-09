<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpSoItemAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_order_id',
        'so_item_id',
        'item_attribute_id',
        'item_code',
        'attribute_name',
        'attr_name',
        'attribute_value',
        'attr_value'
    ];

    public $referencingRelationships = [
        'itemAttribute' => 'item_attribute_id',
        'headerAttribute' => 'attr_name',
        'headerAttributeValue' => 'attr_value'
    ];

    public function itemAttribute()
    {
        return $this->belongsTo(ItemAttribute::class, 'item_attribute_id');
    }

    public function headerAttribute()
    {
        return $this->hasOne(AttributeGroup::class,'id' ,'attr_name');
    }

    public function headerAttributeValue()
    {
        return $this->hasOne(Attribute::class,'id','attr_value');
    }

    public function soItem()
    {
        return $this->belongsTo(ErpSoItem::class, 'so_item_id');
    }
}
