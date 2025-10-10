<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpPslipDynamicFieldHistory extends Model
{
    use HasFactory;
    protected $table = 'erp_pslip_dynamic_fields_history';
    protected $fillable = [
        'source_id',
        'header_id',
        'dynamic_field_id',
        'dynamic_field_detail_id',
        'name',
        'value'
    ];
    
    public function pslip()
    {
        return $this -> belongsTo(ErpSaleOrderHistory::class, 'header_id');
    }
}
