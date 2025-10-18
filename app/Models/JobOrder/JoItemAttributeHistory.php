<?php

namespace App\Models\JobOrder;

use App\Models\Item;
use App\Models\Attribute;
use App\Models\ItemAttribute;
use App\Models\AttributeGroup;
use App\Traits\UserStampTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JoItemAttributeHistory extends Model
{
    use HasFactory, UserStampTrait, SoftDeletes;
    protected $table = 'erp_jo_item_attributes_history';
    protected $fillable = [
        'jo_id',
        'source_id',
        'jo_item_id',
        'item_attribute_id',
        'item_id',
        'item_code',
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
    public function itemAttribute()
    {
        return $this->belongsTo(ItemAttribute::class, 'item_attribute_id');
    }
    public function headerAttribute()
    {
        return $this->hasOne(AttributeGroup::class, 'id', 'attribute_name');
    }
    public function headerAttributeValue()
    {
        return $this->hasOne(Attribute::class, 'id', 'attribute_value');
    }
}
