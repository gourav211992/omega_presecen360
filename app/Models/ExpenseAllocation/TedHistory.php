<?php

namespace App\Models\ExpenseAllocation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\TaxDetail;

class TedHistory extends Model
{
    use HasFactory;

    protected $table = 'erp_exp_allocation_ted_history';

    protected $fillable = [
        'source_id',
        'header_id',
        'detail_id',
        'ted_id',
        'ted_type',
        'ted_level',
        'book_code',
        'document_number',
        'ted_name',
        'ted_code',
        'assesment_amount',
        'ted_percentage',
        'ted_amount',
        'applicability_type'
    ];

    public function header()
    {
        return $this->belongsTo(Header::class, 'header_id');
    }

    public function headerHistory()
    {
        return $this->belongsTo(HeaderHistory::class, 'header_history_id');
    }

    public function detail()
    {
        return $this->belongsTo(Detail::class, 'detail_id');
    }

    public function detailHistory()
    {
        return $this->belongsTo(DetailHistory::class, 'detail_history_id');
    }

    public function source()
    {
        return $this->belongsTo(Ted::class, 'source_id');
    }

    public function taxDetail()
    {
        return $this->belongsTo(TaxDetail::class, 'ted_id');
    }
}
