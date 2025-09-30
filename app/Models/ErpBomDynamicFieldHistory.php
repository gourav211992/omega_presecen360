<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpBomDynamicFieldHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'source_id',
        'header_id',
        'dynamic_field_id',
        'dynamic_field_detail_id',
        'name',
        'value'
    ];

    public function bom()
    {
        return $this -> belongsTo(BomHistory::class,'header_id');
    }
}
