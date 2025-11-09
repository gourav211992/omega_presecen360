<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UserStampTrait;
use App\Traits\FileUploadTrait;

class ErpRepItemDefectLog extends Model
{
    use FileUploadTrait, SoftDeletes,UserStampTrait;

    protected $table = 'erp_rep_item_defect_logs';

    protected $fillable = [
        'repair_order_id',
        'rep_item_id',
        'defect_severity',
        'defect_type',
        'damage_nature',
        'remarks',
        'rejuvenate_item_id',
        'rejuvenate_item_code',
        'rejuvenate_item_name',
        'rejuvenate_item_attributes',
        'service_item_id',
        'service_item_code',
        'service_item_name',
        'vendor_id',
        'repair_remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

     public function item()
    {
        return $this->belongsTo(ErpRepItem::class, 'rep_item_id');
    }

    public function repairOrder()
    {
        return $this->belongsTo(ErpRepairOrder::class, 'repair_order_id');
    }
    public function media()
    {
        return $this->morphMany(ErpRepMedia::class, 'model');
    }

    public function rejuvenateItem()
    {
        return $this->belongsTo(Item::class, 'rejuvenate_item_id');
    }

    public function serviceItem()
    {
        return $this->belongsTo(Item::class, 'service_item_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

       public function createdBy()
    {
        return $this->belongsTo(AuthUser::class, 'created_by','id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(AuthUser::class, 'updated_by','id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(AuthUser::class, 'deleted_by','id');
    }
}
