<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * 创建一封新的邮件实例。
     *
     * @param string $purpose 验证码用途：register（注册）/ reset（找回密码）
     */
    public function __construct(
        public string $code,
        public string $appName = '',
        public string $purpose = 'register',
    ) {}

    /**
     * 获取邮件的信封（主题等）。
     */
    public function envelope(): Envelope
    {
        $subject = $this->purpose === 'reset'
            ? __('email.subject_reset', ['appName' => $this->appName])
            : __('email.subject_register', ['appName' => $this->appName]);

        return new Envelope(subject: $subject);
    }

    /**
     * 获取邮件内容定义。
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-code',
        );
    }
}
