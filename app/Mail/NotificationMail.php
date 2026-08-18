<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 站内行为通知邮件
 *
 * 当用户开启了某类通知的"邮件通知"开关时，由 NotificationService 发送。
 *
 * @param string $content  渲染好的文案（模板已替换 {value} 为发起者用户名）
 * @param string $sender   行为发起者用户名
 * @param string $type     通知类型（见 NotificationService::TYPES）
 */
class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $content,
        public string $sender = '',
        public string $type = '',
        public string $appName = '',
    ) {}

    /**
     * 获取邮件的信封（主题等）。
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: __('email.subject_notification', ['appName' => $this->appName]));
    }

    /**
     * 获取邮件内容定义。
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
        );
    }
}
