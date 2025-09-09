<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Helpers\ConstantHelper;
use App\Traits\Deletable;

class AlternateUOM extends Model
{
    use HasFactory,Deletable,SoftDeletes;
    
    protected $table = 'erp_alternate_uoms';

    protected $fillable = [
        'item_id',
        'uom_id',
        'conversion_to_inventory',
        'cost_price',
        'sell_price',
        'is_selling',
        'is_purchasing'
    ];

    protected $casts = [
        'conversion_to_inventory' => 'decimal:2',
        'is_selling' => 'boolean',
        'is_purchasing' => 'boolean'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function uom()
    {
        return $this->belongsTo(Unit::class, 'uom_id');
    }

}
