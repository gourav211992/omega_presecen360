<?php

namespace App\Models\ExpenseAllocation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\TaxDetail;

class Ted extends Model
{
    use HasFactory;

    protected $table = 'erp_exp_allocation_ted';

    protected $fillable = [
        'header_id',
        'detail_id',
        'ted_id',
        'ted_type',
        'ted_name',
        'ted_level',
        'book_code',
        'document_number',
        'ted_code',
        'assesment_amount',
        'ted_percentage',
        'ted_amount',
        'applicability_type'
    ];

    public function header()
    {
        return $this->belongsTo(Header::class);
    }

    public function detail()
    {
        return $this->belongsTo(Detail::class);
    }

    public function taxDetail()
    {
        return $this->belongsTo(TaxDetail::class, 'ted_id');
    }
}
