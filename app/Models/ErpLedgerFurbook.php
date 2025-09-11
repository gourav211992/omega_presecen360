<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpLedgerFurbook extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'company_id',
        'organization_id',
        'book_id',
        'ledgers',
    ];
}
