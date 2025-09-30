<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpPslipItemDetailHistory extends Model
{
    protected $table="erp_pslip_item_details_history";
    use HasFactory;

    protected $fillable = [
        'pslip_id',
        'pslip_item_id',
        'bundle_no',
        'bundle_type',
        'qty'
    ];
 
    /**
     * Relation: A bundle belongs to a Pslip Item
     */
    public function pslipItem()
    {
        return $this->belongsTo(ErpPslipItemHistory::class, 'pslip_item_id', 'id');
    }

}
