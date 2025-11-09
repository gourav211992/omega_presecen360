<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherHistory extends Model
{
    protected $table = 'erp_vouchers_history';

    use HasFactory;

    protected $fillable = [
        'source_id',
        'voucher_no',
        'voucher_name',
        'book_type_id',
        'book_id',
        'date',
        'amount',
        'document',
        'remarks',
        'approvalLevel',
        'approvalStatus',
        'group_id',
        'company_id',
        'organization_id',
        'voucherable_type',
        'voucherable_id',
        'revision_number',
        'revision_date',
        'location',
        'document_date',
        'doc_number_type',
        'doc_reset_pattern',
        'doc_prefix',
        'doc_suffix',
        'doc_no',
        'currency_id',
        'currency_code',
        'org_currency_id',
        'org_currency_code',
        'org_currency_exg_rate',
        'comp_currency_id',
        'comp_currency_code',
        'comp_currency_exg_rate',
        'group_currency_id',
        'group_currency_code',
        'group_currency_exg_rate',
        'reference_service',
        'reference_doc_id',
        'document_status',
        'created_by',
        'approval_level'
    ];


    public function documents()
    {
        return $this->belongsTo(BookType::class, 'book_type_id');
    }

    public function series()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function items()
    {
        return $this->hasMany(ItemDetailHistory::class, 'voucher_id');
    }

    public function ledger_items()
    {
        return $this->hasMany(ItemDetailHistory::class, 'voucher_id')->select('credit_amt AS credit_amount', 'debit_amt AS debit_amount', 'ledger_parent_id', 'ledger_parent_id AS ledger_group_id', 'ledger_id', 'entry_type', 'due_date');
    }

    public function approvals()
    {
        return $this->hasMany(ApprovalProcess::class);
    }

    public function voucherable()
    {
        return $this->morphTo();
    }
}
