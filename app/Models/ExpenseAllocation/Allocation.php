<?php
namespace App\Models\ExpenseAllocation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Allocation extends Model
{
    use HasFactory;

    protected $table = 'erp_exp_alc_allocations';

    protected $fillable = [
        'header_id',
        'po_detail_id',
        'grn_detail_id',
        'amount'
    ];

    public function source()
    {
        return $this->hasOne(AllocationHistory::class, 'source_id');
    }

    public function header()
    {
        return $this->belongsTo(Header::class, 'header_id');
    }

    public function poDetail()
    {
        return $this->belongsTo(PoDetail::class, 'po_detail_id');
    }

    public function grnDetail()
    {
        return $this->belongsTo(GrnDetail::class, 'grn_detail_id');
    }
}
