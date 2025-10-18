<?php

namespace App\Models\JobOrder;

use App\Traits\UserStampTrait;
use App\Models\TermsAndCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JoTerm extends Model
{
    use HasFactory, UserStampTrait, SoftDeletes;

    protected $table = 'erp_jo_terms';

    protected $fillable = [
        'jo_id',
        'term_id',
        'term_code',
        'remarks'
    ];

    public function termAndCondition()
    {
        return $this->belongsTo(TermsAndCondition::class, 'term_id');
    }

}
