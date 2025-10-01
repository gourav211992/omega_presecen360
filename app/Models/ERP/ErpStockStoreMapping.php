<?php

namespace App\Models\ERP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ErpSubStore;

class ErpStockStoreMapping extends Model
{
    use HasFactory;
    protected $table = 'erp_stock_store_mappings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'stock_type',
        'group_id',
        'organization_id',
        'company_id',
        'sub_store_id',
        'store_id',
        'is_primary',
    ];
    protected $appends = [
        'subStore'
    ];
    public function subStore()
    {
        return $this->belongsTo(ErpSubStore::class, 'sub_store_id');
    }

}
