<?php

namespace App\Models\JobOrder;

use App\Models\Hsn;
use App\Models\TaxDetail;
use App\Models\ExpenseMaster;
use App\Models\DiscountMaster;
use App\Traits\UserStampTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobOrderTedHistory extends Model
{
    use HasFactory, SoftDeletes, UserStampTrait;
    protected $table = 'erp_job_order_ted_history';
    protected $fillable = [
        'jo_id',
        'jo_product_id',
        'source_id',
        'hsn_id',
        'tax_amount',
        'tax_breakup',
        'ted_type',
        'ted_level',
        'ted_id',
        'ted_name',
        'assessment_amount',
        'ted_perc',
        'ted_amount',
        'applicable_type',
    ];

    // protected $appends = [
    //     'ted_name'
    // ];

    protected $casts = [
        'tax_breakup' => 'array',
    ];

    public $referencingRelationships = [
        'hsn' => 'hsn_id',
        'taxDetail' => 'ted_id',
    ];

    public function getTaxBreakupAttribute($value)
    {
        return json_decode($value, true);
    }

    // public function getTedNameAttribute()
    // {
    //     $tedName = null;
    //     $tedId = $this->ted_id ?? null;
    //     if (!$tedId) {
    //         return null;
    //     }
    //     switch ($this->ted_type ?? null) {
    //         case 'Tax':
    //             $tedName = TaxDetail::where('id', $tedId)->value('tax_type');
    //             break;

    //         case 'Expense':
    //             $tedName = ExpenseMaster::where('id', $tedId)->value('name');
    //             break;

    //         case 'Discount':
    //             $tedName = DiscountMaster::where('id', $tedId)->value('name');
    //             break;

    //         default:
    //             $tedName = null;
    //             break;
    //     }
    //     return $tedName;
    // }

    public function hsn()
    {
        return $this->belongsTo(Hsn::class);
    }

    public function jo()
    {
        return $this->belongsTo(JobOrderHistory::class, 'jo_id');
    }

    public function joProduct()
    {
        return $this->belongsTo(JoProductHistory::class, 'jo_product_id');
    }

    public function taxDetail()
    {
        return $this->belongsTo(TaxDetail::class, 'ted_id', 'id');
    }
}
