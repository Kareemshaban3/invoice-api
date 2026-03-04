<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\MailSetting;
use Illuminate\Support\Facades\Config;

class ApplyMailConfig
{
    public function handle($request, Closure $next)
    {
        $mail = MailSetting::first();

        if ($mail) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $mail->smtp_host);
            Config::set('mail.mailers.smtp.port', $mail->smtp_port);
            Config::set('mail.mailers.smtp.username', $mail->smtp_username);
            Config::set('mail.mailers.smtp.password', $mail->smtp_password);
            Config::set('mail.mailers.smtp.encryption', $mail->smtp_tls ? 'tls' : 'ssl');
            Config::set('mail.from.address', $mail->mail_from_email);
            Config::set('mail.from.name', $mail->mail_from_name);
        }

        return $next($request);
    }
}
