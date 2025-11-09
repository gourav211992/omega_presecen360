<?php

namespace App\Models;
use App\Helpers\ConstantHelper;
use App\Traits\DateFormatTrait;
use App\Traits\DefaultGroupCompanyOrg;
use App\Traits\DynamicFieldsTrait;
use App\Traits\FileUploadTrait;
use App\Traits\UserStampTrait;
use App\Models\WHM\ErpWhmJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpRepairOrder extends Model
{
    use HasFactory, DefaultGroupCompanyOrg, FileUploadTrait, DateFormatTrait, UserStampTrait, DynamicFieldsTrait;

    protected $table = 'erp_repair_orders';

    protected $fillable = [
        'group_id',
        'company_id',
        'organization_id',
        'book_id',
        'book_code',
        'store_id',
         'store_name',
        'rgr_sub_store_id',
        'qc_sub_store_id',
        'customer_sub_store_id',
         'currency_id',
        'currency_code',
        'org_currency_id',
        'org_currency_code',
        'org_currency_exg_rate',
        'comp_currency_id',
        'comp_currency_code',
        'comp_currency_exg_rate',
        'group_currency_id',
        'group_currency_code',
        'group_currency_exg_rate',
        'vendor_id',
        'type',
        'defect_status',
        'doc_number_type',
        'doc_reset_pattern',
        'doc_prefix',
        'doc_suffix',
        'doc_no',
        'document_number',
        'document_date',
        'document_status',
        'revision_number',
        'revision_date',
        'approval_level',
        'rgr_id',
        'remarks',
    ];

     // Relations
      public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function group()
    {
        return $this->belongsTo(OrganizationGroup::class, 'group_id');
    }

    public function company()
    {
        return $this->belongsTo(OrganizationCompany::class, 'company_id');
    }

    public function store()
    {
        return $this->belongsTo(ErpStore::class, 'store_id');
    }

     public function rgrSubStore()
    {
        return $this->belongsTo(ErpSubStore::class, 'rgr_sub_store_id');
    }

    public function qcSubStore()
    {
        return $this->belongsTo(ErpSubStore::class, 'qc_sub_store_id');
    }
    
    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(ErpRepItem::class, 'repair_order_id');
    }

    public function media()
    {
        return $this->morphMany(ErpRepMedia::class, 'model');
    }
     public function job()
    {
        return $this->morphOne(ErpWhmJob::class, 'morphable','morphable_type','morphable_id');
    }

    public function rgr()
    {
        return $this->belongsTo(ErpRgr::class, 'rgr_id', 'id');
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

     public function getDisplayStatusAttribute()
    {
        $status = str_replace('_', ' ', $this->document_status);
        return ucwords($status);
    }
    
    public function getDocumentStatusAttribute()
    {
        if ($this->attributes['document_status'] == ConstantHelper::APPROVAL_NOT_REQUIRED) {
            return ConstantHelper::APPROVED;
        }
        return $this->attributes['document_status'];
    }
}
