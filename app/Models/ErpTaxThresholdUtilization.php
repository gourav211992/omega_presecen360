<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpTaxThresholdUtilization extends Model
{
    use HasFactory;
    protected $table = "erp_tax_threshold_utilization";
    protected $fillable = [
        'group_id',
        'company_id',
        'tax_category',
        'tax_type',
        'fy_id',
        'fy_alias',
        'transaction_type',
        'party_id',
        'party_code',
        'party_name',
        'opening_threshold',
        'used_threshold',
        'currency_id',
    ];
}
