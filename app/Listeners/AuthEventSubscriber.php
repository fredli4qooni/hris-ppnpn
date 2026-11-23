<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

class AuthEventSubscriber
{
    /**
     * Handle user login events.
     */
    public function handleUserLogin(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'LOGIN',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $user->updateQuietly(['last_login_at' => now()]);
    }

    /**
     * Handle user failed login events.
     */
    public function handleUserLoginFailed(Failed $event): void
    {
        $attemptedIdentifier = $event->credentials['login']
            ?? $event->credentials['username']
            ?? $event->credentials['email']
            ?? 'unknown';

        AuditLog::create([
            'user_id' => $event->user?->id,
            'action' => 'LOGIN_FAILED',
            'entity_type' => User::class,
            'entity_id' => $event->user?->id,
            'new_values' => [
                'attempted_identifier' => $attemptedIdentifier,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle user logout events.
     */
    public function handleUserLogout(Logout $event): void
    {
        if ($event->user) {
            AuditLog::create([
                'user_id' => $event->user->id,
                'action' => 'LOGOUT',
                'entity_type' => User::class,
                'entity_id' => $event->user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleUserLogin',
            Failed::class => 'handleUserLoginFailed',
            Logout::class => 'handleUserLogout',
        ];
    }
}
