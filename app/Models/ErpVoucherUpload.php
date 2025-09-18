<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpVoucherUpload extends Model
{
    use HasFactory;

    protected $table = 'erp_voucher_uploads';
    protected $fillable = [
        'book_id',
        'book_type_id',
        'document_date',
        'location',
        'currency_id',
        'exchange_rate',
        'ledger_id',
        'ledger_code',
        'ledger_name',
        'group_id',
        'group_name',
        'debit_amount',
        'credit_amount',
        'debit_amount_org',
        'credit_amount_org',
        'debit_amount_comp',
        'credit_amount_comp',
        'debit_amount_group',
        'credit_amount_group',
        'cost_center_id',
        'cost_center_name',
        'remark',
        'org_currency_id',
        'comp_currency_id',
        'group_currency_id',
        'org_exchange_rate',
        'comp_exchange_rate',
        'group_exchange_rate',
        'row_number',
        'reason',
        'migrate_status',
        'voucher_id',
        'created_by',
        'organization_id',
    ];

    protected $casts = [
        'reason' => 'array',
        'document_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'debit_amount_org' => 'decimal:2',
        'credit_amount_org' => 'decimal:2',
        'debit_amount_comp' => 'decimal:2',
        'credit_amount_comp' => 'decimal:2',
        'debit_amount_group' => 'decimal:2',
        'credit_amount_group' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'org_exchange_rate' => 'decimal:4',
        'comp_exchange_rate' => 'decimal:4',
        'group_exchange_rate' => 'decimal:4',
    ];

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'auth_user_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
