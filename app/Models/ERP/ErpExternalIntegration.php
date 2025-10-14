<?php
namespace App\Models\ERP;

use App\Models\Book;
use App\Models\ErpCustomer;
use App\Models\ErpStore;
use App\Models\Organization;
use App\Models\OrganizationCompany;
use App\Models\OrganizationGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\DefaultGroupCompanyOrgScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ErpExternalIntegration extends Model
{
    use SoftDeletes,HasFactory;


     /**
     * The table associated with the model.
     */
    protected $table = 'erp_external_integrations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'group_id',
        'company_id',
        'organization_id',
        'trip_book_id',
        'so_book_id',
        'dnote_book_id',
        'store_id',
        'customer_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['deleted_at', 'customer_id'];

    public function customer()
    {
        return $this->belongsTo(ErpCustomer::class, 'customer_id')->withoutGlobalScope(DefaultGroupCompanyOrgScope::class);
    }

    public function soBook()
    {
        return $this->belongsTo(Book::class, 'so_book_id');
    }

    public function tripBook()
    {
        return $this->belongsTo(Book::class, 'trip_book_id');
    } 
     public function dnote()
    {
        return $this->belongsTo(Book::class, 'dnote_book_id');
    }

    public function dnoteBook()
    {
        return $this->belongsTo(Book::class, 'dnote_book_id');
    }

    public function store()
    {
        return $this->belongsTo(ErpStore::class)->withoutGlobalScope(DefaultGroupCompanyOrgScope::class);
    }


    public function group()
    {
        return $this->belongsTo(OrganizationGroup::class, 'group_id');
    }

    public function company()
    {
        return $this->belongsTo(OrganizationCompany::class, 'company_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
    public function stockStoreMapping()
    {
        return $this->hasMany(ErpStockStoreMapping::class, 'store_id','store_id')->orderBy('stock_type');
    }
}

?>
