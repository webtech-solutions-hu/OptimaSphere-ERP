<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public User $approver;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $approver)
    {
        $this->approver = $approver;
    }

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
            ->subject('Your Account Has Been Approved!')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Great news! Your account has been approved.')
            ->line('**Account Status:**');

        // Check what's still needed
        if (!$notifiable->isEmailVerified()) {
            $message->line('✗ Email Verification: **Required** - Please check your email for the verification link.')
                ->line('✓ Account Activation: Completed')
                ->line('✓ Account Approval: Completed');
        } elseif (!$notifiable->is_active) {
            $message->line('✓ Email Verification: Completed')
                ->line('✗ Account Activation: **Pending** - Your account needs to be activated by an administrator.')
                ->line('✓ Account Approval: Completed');
        } else {
            $message->line('✓ Email Verification: Completed')
                ->line('✓ Account Activation: Completed')
                ->line('✓ Account Approval: Completed')
                ->line('')
                ->line('🎉 **You can now access the system!**');
        }

        $message->line('')
            ->line('**Approved By:** ' . $this->approver->name)
            ->line('**Approved At:** ' . now()->format('M d, Y H:i:s'));

        if ($notifiable->canAccessSystem()) {
            $message->action('Login to ' . $appName, $loginUrl)
                ->line('Thank you for joining us!');
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
            'approved_by' => $this->approver->id,
            'approver_name' => $this->approver->name,
            'approved_at' => now()->toDateTimeString(),
        ];
    }
}
