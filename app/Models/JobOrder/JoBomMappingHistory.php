<?php

namespace App\Models\JobOrder;

use App\Models\Item;
use App\Models\StockLedger;
use App\Traits\UserStampTrait;
use Illuminate\Database\Eloquent\Model;
use App\Models\JobOrder\JobOrderHistory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JoBomMappingHistory extends Model
{
    use HasFactory, SoftDeletes, UserStampTrait;
    protected $table = 'erp_jo_bom_mapping_history';
    protected $fillable = [
        'jo_id',
        'jo_product_id',
        'source_id',
        'so_id',
        'bom_id',
        'bom_detail_id',
        'item_id',
        'item_code',
        'uom_id',
        'attributes',
        'rm_type',
        'bom_qty',
        'qty',
        'station_id',
        'section_id',
        'sub_section_id'
    ];

    protected $casts = ['attributes' => 'array'];

    public function jo()
    {
        return $this->belongsTo(JobOrderHistory::class, 'jo_id');
    }

    public function joProduct()
    {
        return $this->belongsTo(JoProductHistory::class, 'jo_product_id');
    }

    public function stockMappings()
    {
        return $this->hasMany(StockLedger::class, 'item_id', 'item_id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
