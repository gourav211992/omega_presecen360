<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FileUploadTrait;

class ErpRcaItem extends Model
{
    use HasFactory, FileUploadTrait;

    protected $table = 'erp_rca_items';

    protected $fillable = [
        'rca_header_id',
        'item_id',
        'rgr_job_detail_id',
        'rgr_item_segregation_id',
        'item_code',
        'item_name',
        'item_uid',
        'uom_id',
        'uom_code',
        'inventory_uom_id',
        'inventory_uom_code',
        'inventory_uom_qty',
        'scheduled_qty',
        'missing_qty',
        'remark',
    ];


    public function header()
    {
        return $this->belongsTo(ErpRcaHeader::class, 'rca_header_id');
    }

    public function attributes()
    {
        return $this->hasMany(ErpRcaItemAttribute::class, 'rca_item_id');
    }

    public function media()
    {
        return $this->morphMany(ErpRcaMedia::class, 'model');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }


    public function uom()
    {
        return $this->belongsTo(Unit::class, 'uom_id');
    }

    public function inventoryUom()
    {
        return $this->belongsTo(Unit::class, 'inventory_uom_id');
    }

     public function rgrSegregation()
    {
        return $this->hasOne(ErpRgrItemSegregation::class, 'id', 'rgr_item_segregation_id');
    }

    public function rgrJobDetail()
    {
        return $this->belongsTo(ErpWhmJob::class, 'rgr_job_detail_id');
    }

}
