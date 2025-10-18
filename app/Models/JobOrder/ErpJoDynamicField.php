<?php

namespace App\Models\JobOrder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ErpJoDynamicField extends Model
{
    use HasFactory;

    protected $fillable = [
        'header_id',
        'dynamic_field_id',
        'dynamic_field_detail_id',
        'name',
        'value'
    ];

    public function jo()
    {
        return $this->belongsTo(JobOrder::class, 'header_id');
    }
}
