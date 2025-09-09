<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PiItemAttributeHistory extends Model
{
    use HasFactory;

    protected $table = 'erp_pi_item_attributes_history';

    protected $fillable = [
        'source_id',
        'pi_id',
        'pi_item_id',
        'item_attribute_id',
        'item_id',
        'item_code',
        'attribute_group_id',
        'attribute_id',
        'attribute_name',
        'attribute_value',
    ];
    
    public $referencingRelationships = [
        'item' => 'item_id',
        'itemAttribute' => 'item_attribute_id',
        'headerAttribute' => 'attribute_name',
        'headerAttributeValue' => 'attribute_value'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
    
    public function item_attribute()
    {
        return $this->belongsTo(ItemAttribute::class, 'item_attribute_id');
    }

    public function headerAttribute()
    {
        return $this->hasOne(AttributeGroup::class,'id' ,'attribute_name');
    }

    public function headerAttributeValue()
    {
        return $this->hasOne(Attribute::class,'id','attribute_value');
    }

}
