<?php

namespace App\Models\Scrap;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ErpScrapDynamicFieldHistory extends Model
{
    use HasFactory;
    protected $table = 'erp_scrap_dynamic_fields_history';

    protected $fillable = [
        'source_id',
        'header_id',
        'dynamic_field_id',
        'dynamic_field_detail_id',
        'name',
        'value'
    ];

    public function header()
    {
        return $this->belongsTo(ErpScrapHistory::class, 'header_id');
    }
}
