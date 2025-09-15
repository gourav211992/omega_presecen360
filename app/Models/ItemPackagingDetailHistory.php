<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemPackagingDetailHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erp_item_packaging_details_history';

    protected $fillable = [
        'source_id',
        'item_id',
        'packet_name',
        'packet_no',
        'length_in_feet',
        'breadth_in_feet',
        'height_in_feet',
        'storage_weight',
        'storage_volume',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function itemPackagingDetail()
    {
        return $this->belongsTo(ItemPackagingDetail::class, 'source_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}