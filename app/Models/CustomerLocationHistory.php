<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerLocationHistory extends Model
{
    use HasFactory;

    protected $table = 'erp_customer_stores_history';

    protected $fillable = [
        'source_id',
        'customer_id',
        'organization_id',
        'location_id',
        'store_id'
    ];

      public function customer()
    {
        return $this -> belongsTo(Customer::class, 'customer_id');
    }

    public function store()
    {
        return $this -> belongsTo(ErpStore::class, 'location_id');
    }

    public function organization()
    {
        return $this -> belongsTo(Organization::class, 'organization_id');
    }

    public function sub_store()
    {
        return $this -> belongsTo(ErpSubStore::class, 'store_id');
    }
}
