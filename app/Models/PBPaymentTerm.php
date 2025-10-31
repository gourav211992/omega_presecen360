<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class PBPaymentTerm extends Model
{

    protected $table = 'erp_pb_payment_terms';

    protected $fillable = [
        'pb_header_id', 'reference_id', 'reference_type', 'payment_term_id', 'payment_term_detail_id', 'credit_days', 'due_date', 'percent', 'trigger_type',
    ];

    public function details()
    {
        return $this->hasOne(PbHeader::class);
    }
    
}
