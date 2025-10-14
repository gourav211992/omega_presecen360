<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailBox extends Model
{
    use SoftDeletes;

    const STATUS_PENDING   = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_REJECTED  = 2;

    protected $table = 'mail_box';

    protected $fillable = [
        'mail_to',
        'mail_cc',
        'mail_bcc',
        'subject',
        'mail_body',
        'attachment',
        'status',
        'response',
        'category',
        'layout',
    ];

    protected $casts = [
        'mail_cc'     => 'array',
        'mail_bcc'    => 'array',
        'attachment'  => 'array',
    ];
}
