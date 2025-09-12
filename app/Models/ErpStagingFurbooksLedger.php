<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpStagingFurbooksLedger extends Model
{
    use HasFactory;

    protected $table = 'erp_finance_staging_furbooks_ledger';

    protected $fillable = [
        'location_id',
        'organization_id',
        'currency_code',
        'furbooks_code',
        'cost_center',
        'remark',
        'final_remark',
        'document_date',
        'debit_amount',
        'credit_amount',
        'amount',
        'status',
    ];

    protected $casts = [
        'document_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
