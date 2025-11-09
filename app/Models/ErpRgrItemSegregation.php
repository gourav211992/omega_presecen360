<?php

namespace App\Models;

use App\Helpers\RGR\Constants as RGRConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\ConstantHelper;
use App\Traits\FileUploadTrait;

class ErpRgrItemSegregation extends Model
{
    use HasFactory,FileUploadTrait;

    protected $appends = ['segregation_status'];

    protected $fillable = [
        'rgr_id',
        'rgr_item_id',
        'job_item_id',
        'item_id',
        'label_status',
        'delivery_cancel',
        'replacement_item',
        'packing_status',
        'defect_severity',
        'defect_type',
        'damage_nature',
        'remarks',
        'new_item_id',
        'new_item_code',
        'new_item_name',
        'new_item_attributes',
    ];

    public function getSegregationStatusAttribute()
    {
        $statuses = [];
        //Take in account all details
       $packingStatus = $this -> packing_status == 0 ? true : false;
        $deliveryCancel = $this -> delivery_cancel;
        $replacementItem = $this->replacement_item; 
        $extraItem = $this -> rgr_item_id ? false : true;
        $transitDamageNature = ($this->damage_nature == ConstantHelper::DAMAGE_NATURE_TRANSIT_HANDLE_DAMAGE) ? true : false;
        $defectSeverity = $this -> defect_severity;
        $defectType = $this -> defect_type;
        $damageNature = $this -> damage_nature;
        $isWrongProduct = isset($this -> new_item_id) ? true : false;
        
        if ($packingStatus || $deliveryCancel || $replacementItem || $transitDamageNature) {
            $statuses[] = RgrConstants::RGR_SEGREGATION_OK_TO_RECIEVE;
        }
        //Packing Status
        if ($packingStatus) {
            $statuses[] = RgrConstants::RGR_SEGREGATION_PACK_MISSING;
        }
        //Wrong Product 
        if ($isWrongProduct) {
            $statuses[] = RgrConstants::RGR_SEGREGATION_WRONG_PRODUCT;
        }
         //Delivery Cancel 
        if ($deliveryCancel) {
            $statuses[] = RgrConstants::RGR_SEGREGATION_DELIVERY_CANCEL;
        }
         //Replacement Item
        if ($replacementItem) {
            $statuses[] = RgrConstants::RGR_SEGREGATION_REPLACEMENT_ITEM; 
        }
        
         //Extra Item
       if ($extraItem) {
            $statuses[] = RgrConstants::RGR_SEGREGATION_EXTRA_ITEM;
        }

        //Transit Damage 
        if ($transitDamageNature) {
            $statuses[] = RgrConstants::RGR_SEGREGATION_TRANSIT_DAMAGE;
        }

        return $statuses;
    }

     public function media()
    {
        return $this->morphMany(ErpRgrMedia::class, 'model');
    }

     public function rgrItem()
    {
        return $this->belongsTo(ErpRgrItem::class, 'rgr_item_id');
    }
    public function rcaItem()
    {
        return $this->hasOne(ErpRcaItem::class, 'rgr_item_segregation_id');
    }
}