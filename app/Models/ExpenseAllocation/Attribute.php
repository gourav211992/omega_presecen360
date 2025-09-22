<?php
namespace App\Models\ExpenseAllocation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\Item;
use App\Models\ItemAttribute;

class Attribute extends Model
{

    use HasFactory;

    protected $table = "erp_exp_allocation_attributes";
    protected $fillable = [
        'header_id',
        'detail_id',
        'item_id',
        'item_code',
        'item_attribute_id',
        'attr_name',
        'attr_value'
    ];

    protected $appends = [
    ];

    protected $hidden = ['deleted_at'];

    public $referencingRelationships = [
        'itemAttribute' => 'item_attribute_id',
        'headerAttribute' => 'attr_name',
        'headerAttributeValue' => 'attr_value'
    ];

    public function expenseHeader()
    {
        return $this->belongsTo(Header::class);
    }

    public function expenseDetail()
    {
        return $this->belongsTo(Detail::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function itemAttribute()
    {
        return $this->belongsTo(ItemAttribute::class);
    }

}