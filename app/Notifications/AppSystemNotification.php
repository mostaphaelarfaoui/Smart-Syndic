<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppSystemNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'info', // 'info', 'warning', 'success', 'danger'
        public ?string $url = null,
        public ?string $icon = null,
        public array $extra = []
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'      => $this->title,
            'message'    => $this->message,
            'type'       => $this->type,
            'url'        => $this->url,
            'icon'       => $this->icon ?? $this->defaultIcon($this->type),
            'extra'      => $this->extra,
            'created_at' => now()->toIso8601String(),
        ];
    }

    protected function defaultIcon(string $type): string
    {
        return match ($type) {
            'success' => '✨',
            'warning' => '⚠️',
            'danger'  => '🚨',
            default   => '📢',
        };
    }
}
