<?php

namespace App\Mail;

use App\Support\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $code,
        public int $expiryMinutes,
    ) {}

    public function envelope(): Envelope
    {
        $app = Settings::get('branding.app_name', config('app.name'));

        return new Envelope(
            subject: "Your {$app} verification code: {$this->code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.otp-code',
            with: [
                'code' => $this->code,
                'expiryMinutes' => $this->expiryMinutes,
                'appName' => Settings::get('branding.app_name', config('app.name')),
            ],
        );
    }
}
