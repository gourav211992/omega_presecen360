<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOrganizationMapping extends Model
{
    use HasFactory;

    protected $table = 'user_organization_mapping';

    protected $fillable = [
        'user_id',
        'organization_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
