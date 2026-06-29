<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PublicationWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $message,
        protected ?string $actionUrl = null,
        protected ?string $actionLabel = null,
        protected ?string $mailSubject = null,
        protected string $icon = 'bi-bell',
        protected bool $sendMail = true
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->sendMail && !empty($notifiable->email) && filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->mailSubject ?: $this->title)
            ->greeting('Halo, ' . ($notifiable->name ?? 'Pengguna'))
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action($this->actionLabel ?: 'Buka Sistem', $this->actionUrl);
        }

        return $mail->line('Notifikasi ini dikirim otomatis oleh Manajemen Publikasi Statistik.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'icon' => $this->icon,
        ];
    }
}
