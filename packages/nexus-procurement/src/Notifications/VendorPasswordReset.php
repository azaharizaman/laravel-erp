<?php

declare(strict_types=1);

namespace Nexus\Procurement\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Vendor Password Reset Notification
 *
 * Sent to vendor users when they request password reset.
 */
class VendorPasswordReset extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $resetToken
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url("/vendor-portal/reset-password?token={$this->resetToken}&email=" . urlencode($notifiable->email));

        return (new MailMessage)
            ->subject('Password Reset Request - Vendor Portal')
            ->greeting("Hello {$notifiable->full_name}!")
            ->line('You have requested to reset your password for the vendor portal.')
            ->action('Reset Password', $resetUrl)
            ->line('This password reset link will expire in 24 hours.')
            ->line('If you did not request a password reset, please ignore this email.')
            ->salutation('Best regards, Procurement Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reset_token' => $this->resetToken,
            'email' => $notifiable->email,
        ];
    }
}