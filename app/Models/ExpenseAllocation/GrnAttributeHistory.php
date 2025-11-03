<?php
namespace App\Models\ExpenseAllocation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\Item;
use App\Models\ItemAttribute;

class GrnAttributeHistory extends Model
{
    use HasFactory;

    protected $table = "erp_exp_alc_grn_attributes_history";
    protected $fillable = [
        'source_id',
        'header_id',
        'detail_id',
        'item_id',
        'item_code',
        'item_attribute_id',
        'attr_name',
        'attr_value'
    ];

    protected $hidden = ['deleted_at'];

    public $referencingRelationships = [
        'itemAttribute' => 'item_attribute_id',
        'headerAttribute' => 'attr_name',
        'headerAttributeValue' => 'attr_value'
    ];

    public function header()
    {
        return $this->belongsTo(HeaderHistory::class, 'header_id');
    }

    public function source()
    {
        return $this->hasOne(GrnAttributeHistory::class, 'source_id');
    }

    public function grnDetail()
    {
        return $this->belongsTo(GrnDetail::class, 'detail_id');
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