<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PoTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erp_po_terms';

    protected $fillable = [
        'purchase_order_id',
        'term_id',
        'term_code',
        'remarks'
    ];

    public function termAndCondition()
    {
        return $this->belongsTo(TermsAndCondition::class, 'term_id');
    }
}
