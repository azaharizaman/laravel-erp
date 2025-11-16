<?php

declare(strict_types=1);

namespace Nexus\Procurement\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Vendor Portal Invitation Notification
 *
 * Sent to new vendor users with login credentials.
 */
class VendorPortalInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $temporaryPassword
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
        return (new MailMessage)
            ->subject('Welcome to Our Vendor Portal')
            ->greeting("Hello {$notifiable->full_name}!")
            ->line('You have been invited to access our vendor portal.')
            ->line('Your account has been created with the following credentials:')
            ->line("**Email:** {$notifiable->email}")
            ->line("**Temporary Password:** {$this->temporaryPassword}")
            ->action('Login to Vendor Portal', url('/vendor-portal/login'))
            ->line('Please change your password after first login.')
            ->line('If you have any questions, please contact our procurement team.')
            ->salutation('Best regards, Procurement Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'vendor_name' => $notifiable->vendor->name,
            'email' => $notifiable->email,
            'temporary_password' => $this->temporaryPassword,
        ];
    }
}