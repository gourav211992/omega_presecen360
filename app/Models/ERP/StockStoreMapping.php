<?php

namespace App\Models\ERP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockStoreMapping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'erp_stock_store_mappings';
}
