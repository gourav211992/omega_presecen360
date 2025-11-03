<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemDetailHistory extends Model
{
    protected $table = 'erp_item_details_history';

    use HasFactory;

    protected $fillable = [
        'source_id',
        'voucher_id',
        'ledger_id',
        'debit_amt',
        'credit_amt',
        'cost_center_id',
        'notes',
        'date',
        'due_date',
        'opening',
        'opening_type',
        'closing',
        'closing_type',
        'group_id',
        'company_id',
        'organization_id',
        'bank_date',
        'statement_uid',
        'ledger_parent_id',
        'debit_amt_org',
        'credit_amt_org',
        'debit_amt_comp',
        'credit_amt_comp',
        'debit_amt_group',
        'credit_amt_group',
        'entry_type',
        'remarks'
    ];

    protected $appends = [
        'ledger_code',
        'ledger_name',
        'ledger_group_code'
    ];

    public function voucher()
    {
        return $this->belongsTo(VoucherHistory::class, 'voucher_id', 'id');
    }

    public function source()
    {
        return $this->belongsTo(ItemDetail::class, 'source_id');
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function ledger_group()
    {
        return $this->belongsTo(Group::class, 'ledger_parent_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function getLedgerGroupCodeAttribute()
    {
        return optional($this->ledger_group()->first())->name;
    }
    public function getLedgerNameAttribute()
    {
        return optional($this->ledger()->first())->name;
    }
    public function getLedgerCodeAttribute()
    {
        return optional($this->ledger()->first())->code;
    }
}
