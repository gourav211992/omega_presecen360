<?php

namespace App\Models\JobOrder;

use App\Traits\UserStampTrait;
use App\Traits\DateFormatTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JoProductDeliveryHistory extends Model
{
    use HasFactory, DateFormatTrait, UserStampTrait, SoftDeletes;

    protected $table = 'erp_jo_product_delivery_history';

    protected $fillable = [
        'jo_id',
        'source_id',
        'jo_product_id',
        'qty',
        'grn_qty',
        'delivery_date'
    ];
}
