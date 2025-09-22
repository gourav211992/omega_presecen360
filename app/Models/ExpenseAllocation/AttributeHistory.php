<?php
namespace App\Models\ExpenseAllocation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\Item;
use App\Models\ItemAttribute;

class AttributeHistory extends Model
{
    use HasFactory;

    protected $table = "erp_exp_allocation_attributes_history";
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

    protected $appends = [
    ];

    protected $hidden = ['deleted_at'];

    public function header()
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

    public function detailHistory()
    {
        return $this->belongsTo(DetailHistory::class, 'detail_history_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'source_id');
    }

    public function itemAttribute()
    {
        return $this->belongsTo(ItemAttribute::class, 'item_attribute_id');
    }

}