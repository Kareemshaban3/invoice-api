<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MailSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailSettingController extends Controller
{
    public function show()
    {
        return MailSetting::first();
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'mail_from_email' => 'nullable|email',
            'mail_from_name' => 'nullable|string',
            'smtp_host' => 'required|string',
            'smtp_username' => 'required|string',
            'smtp_password' => 'required|string',
            'smtp_port' => 'required|integer',
            'smtp_tls' => 'required|boolean',
        ]);
        $setting = MailSetting::updateOrCreate(
            ['id' => 1],
            $data
        );
        return response()->json([
            'message' => 'Mail settings saved',
            'data' => $setting
        ]);
    }


 public function test(Request $request)
{
    $data = $request->validate([
        'to' => ['required','email']
    ]);

    try {
        \Mail::raw('SMTP Test from Invoice API - '.now(), function ($msg) use ($data) {
            $msg->to($data['to'])
                ->subject('SMTP Test - Invoice API')
                ->from(config('mail.from.address'), config('mail.from.name'));
        });

        return response()->json([
            'message' => 'SMTP OK, email sent',
            'from' => config('mail.from.address'),
            'mailer' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
            ],
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'SMTP FAILED',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
