<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Failed;
use Rappasoft\LaravelAuthenticationLog\Notifications\FailedLogin;

class FailedLoginListener extends EventListener
{
    public function handle($event): void
    {
        if (! $this->isListenerForEvent($event, 'failed', Failed::class)) {
            return;
        }

        if (! $this->isLoggable($event)) {
            return;
        }

        $log = $event->user->authentications()->create([
            'ip_address' => $ip = $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'login_at' => now(),
            'login_successful' => false,
            'location' => config('authentication-log.notifications.new-device.location') ? optional(geoip()->getLocation($ip))->toArray() : null,
        ]);

        if (config('authentication-log.notifications.failed-login.enabled')) {
            $failedLogin = config('authentication-log.notifications.failed-login.template') ?? FailedLogin::class;
            $event->user->notify(new $failedLogin($log));
        }
    }
}
