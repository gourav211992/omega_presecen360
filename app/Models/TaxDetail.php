<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Deletable;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxDetail extends Model
{
    use HasFactory,SoftDeletes,Deletable;

    protected $table = 'erp_tax_details';

    protected $fillable = [
        'tax_id',
        'ledger_id',
        'ledger_group_id',
        'reverse_ledger_id',      
        'reverse_ledger_group_id',
        'tax_type',
        'tax_percentage',
        'place_of_supply',
        'applicability_type',
        'is_purchase',
        'is_sale',
        'status',
    ];

    public function erpTax()
    {
        return $this->belongsTo(Tax::class,'tax_id');
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }
    public function ledgerGroup()
    {
        return $this->belongsTo(Group::class); 
    }

    public function reverseLedger()
    {
        return $this->belongsTo(Ledger::class, 'reverse_ledger_id');
    }

    public function reverseLedgerGroup()
    {
        return $this->belongsTo(Group::class, 'reverse_ledger_group_id');
    }

}
