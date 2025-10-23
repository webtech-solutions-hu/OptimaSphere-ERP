<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $loginUrl = url('/admin/login');
        $appName = config('app.name');

        $message = (new MailMessage)
            ->subject('Your Account Has Been Activated!')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your account has been activated.')
            ->line('**Account Status:**');

        // Check what's still needed
        if (!$notifiable->isEmailVerified()) {
            $message->line('✗ Email Verification: **Required** - Please check your email for the verification link.')
                ->line('✓ Account Activation: Completed')
                ->line(($notifiable->isApproved() ? '✓' : '✗') . ' Account Approval: ' . ($notifiable->isApproved() ? 'Completed' : '**Pending**'));
        } elseif (!$notifiable->isApproved()) {
            $message->line('✓ Email Verification: Completed')
                ->line('✓ Account Activation: Completed')
                ->line('✗ Account Approval: **Pending** - Your account is awaiting approval from an administrator.');
        } else {
            $message->line('✓ Email Verification: Completed')
                ->line('✓ Account Activation: Completed')
                ->line('✓ Account Approval: Completed')
                ->line('')
                ->line('🎉 **You can now access the system!**');
        }

        if ($notifiable->canAccessSystem()) {
            $message->action('Login to ' . $appName, $loginUrl)
                ->line('Welcome aboard!');
        } else {
            $message->line('You will receive another notification once all requirements are met.')
                ->line('Thank you for your patience!');
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'activated_at' => now()->toDateTimeString(),
        ];
    }
}
