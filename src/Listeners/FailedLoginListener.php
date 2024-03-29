<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Rappasoft\LaravelAuthenticationLog\Notifications\FailedLogin;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;

class FailedLoginListener
{
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle($event): void
    {
        $listener = config('authentication-log.events.failed', Failed::class);

        if (! $event instanceof $listener) {
            return;
        }

        if ($event->user) {
            if(! in_array(AuthenticationLoggable::class, class_uses_recursive(get_class($event->user)))){
                return;
            }

            if (config('authentication-log.behind_cdn')) {
                $ip = $this->request->server(config('authentication-log.behind_cdn.http_header_field'));
            } else {
                $ip = $this->request->ip();
            }

            $log = $event->user->authentications()->create([
                'ip_address' => $ip,
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
}
