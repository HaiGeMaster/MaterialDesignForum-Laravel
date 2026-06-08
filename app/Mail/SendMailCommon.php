<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMailCommon extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $data;

    /**
     * @param string $subject  邮件主题
     * @param array  $data     传递给视图的数据
     * @param string $view     邮件视图模板，默认 'emails.common'
     */
    public function __construct(string $subject, array $data = [], string $view = 'emails.common')
    {
        $this->subject = $subject;
        $this->data    = $data;
        $this->view    = $view;
    }

    public function build()
    {
        return $this->subject($this->subject)
                    ->view($this->view, $this->data);
    }
}
