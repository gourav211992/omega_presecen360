<?php

namespace App\Models;

use App\Traits\DateFormatTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PoItemDelivery extends Model
{
    use HasFactory, DateFormatTrait, SoftDeletes;

    protected $table = 'erp_po_item_delivery';

    protected $fillable = [
        'purchase_order_id',
        'po_item_id',
        'qty',
        'grn_qty',
        'delivery_date'
    ];
}
