<?php
namespace App\Models\ExpenseAllocation;

use App\Models\User;
use App\Helpers\Helper;

use App\Models\Book;
use App\Models\Group;
use App\Models\Vendor;
use App\Models\Address;
use App\Models\ErpStore;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\CostCenter;
use App\Models\ErpAddress;
use App\Models\PaymentTerm;
use App\Models\InvoiceBook;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\OrganizationCompany;


use App\Traits\DateFormatTrait;
use App\Traits\FileUploadTrait;
use App\Traits\DynamicFieldsTrait;
use App\Traits\DefaultGroupCompanyOrg;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HeaderHistory extends Model
{
    use HasFactory, SoftDeletes, DateFormatTrait, FileUploadTrait, DefaultGroupCompanyOrg, DynamicFieldsTrait;

    protected $table = 'erp_exp_alc_headers_history';
    protected $fillable = [
        'source_id',
        'organization_id',
        'group_id',
        'company_id',
        'book_id',
        'book_code',
        'doc_number_type',
        'doc_reset_pattern',
        'doc_prefix',
        'doc_suffix',
        'doc_no',
        'store_id',
        'sub_store_id',
        'cost_center_id',
        'document_number',
        'document_date',
        'document_status',
        'revision_number',
        'revision_date',
        'approval_level',
        'reference_number',
        'supplier_invoice_no',
        'supplier_invoice_date',
        'eway_bill_no',
        'consignment_no',
        'transporter_name',
        'vehicle_no',
        'transaction_currency',
        'org_currency_id',
        'org_currency_code',
        'org_currency_exg_rate',
        'comp_currency_id',
        'comp_currency_code',
        'comp_currency_exg_rate',
        'group_currency_id',
        'group_currency_code',
        'group_currency_exg_rate',
        'total_po_value',
        'total_grn_value',
        'total_allocated_value',
        'total_landed_cost_value',
        'remark',
        'status',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'gst_details' => 'array',
    ];

    public static function boot()
    {
        parent::boot();
        $user = Helper::getAuthenticatedUser();
        if ($user) {
            static::creating(function ($model) use ($user) {
                $model->created_by = $user->auth_user_id;
            });

            static::updating(function ($model) use ($user) {
                $model->updated_by = $user->auth_user_id;
            });

            static::deleting(function ($model) use ($user) {
                $model->deleted_by = $user->auth_user_id;
            });
        }
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function company()
    {
        return $this->belongsTo(OrganizationCompany::class, 'company_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function header()
    {
        return $this->belongsTo(Header::class, 'header_id');
    }

    public function getDisplayStatusAttribute()
    {
        $status = str_replace('_', ' ', $this->document_status);
        return ucwords($status);
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'series_id');
    }

    public function erpStore()
    {
        return $this->belongsTo(ErpStore::class, 'store_id');
    }

    public function store_address()
    {
        return $this->morphOne(ErpAddress::class, 'addressable', 'addressable_type', 'addressable_id')->where('type', 'location')->with(['city', 'state', 'country']);
    }

    public function poDetails()
    {
        return $this->hasMany(PoDetail::class, 'header_id');
    }

    public function grnDetails()
    {
        return $this->hasMany(GrnDetail::class, 'header_id');
    }

    public function attachment(): void
    {
        $this->addMediaCollection('attachment');
    }

    public function addresses()
    {
        return $this->morphMany(ErpAddress::class, 'addressable', 'addressable_type', 'addressable_id');
    }

    public function organizationAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', 'default');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function media()
    {
        return $this->morphMany(ExpMedia::class, 'model');
    }

    public function costCenters()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
