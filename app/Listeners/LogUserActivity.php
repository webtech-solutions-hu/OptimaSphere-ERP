<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\PasswordResetRequestedNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordResetLinkSent;

class LogUserActivity
{
    /**
     * Handle user login events.
     */
    public function handleLogin(Login $event): void
    {
        ActivityLog::log(
            'login',
            "{$event->user->name} logged in",
            $event->user
        );
    }

    /**
     * Handle user logout events.
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            ActivityLog::log(
                'logout',
                "{$event->user->name} logged out",
                $event->user
            );
        }
    }

    /**
     * Handle user registration events.
     */
    public function handleRegistered(Registered $event): void
    {
        ActivityLog::log(
            'registration',
            "{$event->user->name} registered a new account",
            $event->user
        );
    }

    /**
     * Handle email verified events.
     */
    public function handleVerified(Verified $event): void
    {
        ActivityLog::log(
            'email_confirmed',
            "{$event->user->name} confirmed their email address",
            $event->user
        );
    }

    /**
     * Handle password reset link sent events.
     */
    public function handlePasswordResetLinkSent(PasswordResetLinkSent $event): void
    {
        ActivityLog::log(
            'password_reset_requested',
            "{$event->user->name} requested a password reset",
            $event->user
        );

        // Notify approvers about password reset request
        $approvers = User::whereHas('roles', function ($query) {
            $query->where('supervisor', true)
                ->orWhereIn('slug', ['power-user', 'it']);
        })->get();

        \Illuminate\Support\Facades\Notification::send($approvers, new PasswordResetRequestedNotification($event->user));
    }

    /**
     * Handle password reset events.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        ActivityLog::log(
            'password_changed',
            "{$event->user->name} changed their password via reset",
            $event->user
        );
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            Login::class,
            [LogUserActivity::class, 'handleLogin']
        );

        $events->listen(
            Logout::class,
            [LogUserActivity::class, 'handleLogout']
        );

        $events->listen(
            Registered::class,
            [LogUserActivity::class, 'handleRegistered']
        );

        $events->listen(
            Verified::class,
            [LogUserActivity::class, 'handleVerified']
        );

        $events->listen(
            PasswordResetLinkSent::class,
            [LogUserActivity::class, 'handlePasswordResetLinkSent']
        );

        $events->listen(
            PasswordReset::class,
            [LogUserActivity::class, 'handlePasswordReset']
        );
    }
}
