<?php
namespace App\Models\ExpenseAllocation;

use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DynamicFieldHistory extends Model
{
    use HasFactory, FileUploadTrait;

    protected $table = 'erp_exp_alc_dynamic_fields_history';

    protected $fillable = [
        'header_id',
        'source_id',
        'dynamic_field_id',
        'dynamic_field_detail_id',
        'name',
        'value'
    ];
}
