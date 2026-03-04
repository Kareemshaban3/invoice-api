<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $fillable = [
        'mail_from_email',
        'mail_from_name',
        'smtp_host',
        'smtp_username',
        'smtp_password',
        'smtp_port',
        'smtp_tls'
    ];
}
