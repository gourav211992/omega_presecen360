<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpMaterialIssueTed extends Model
{
    use HasFactory;
    
    protected $table = 'erp_material_issue_ted';

    protected $referenceTables = [
        'taxDetail' => 'ted_id'
    ];

    protected $fillable = [
        'material_issue_id',
        'mi_item_id',
        'ted_type',
        'ted_level',
        'ted_id',
        'ted_group_code',
        'ted_name',
        'assessment_amount',
        'ted_percentage',
        'ted_amount',
        'applicable_type',
    ];

    public function taxDetail()
    {
        return $this->belongsTo(TaxDetail::class, 'ted_id');
    }
}
