<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;

class LogoutListener
{
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle($event): void
    {
        $listener = config('authentication-log.events.logout', Logout::class);

        if (! $event instanceof $listener) {
            return;
        }

        if ($event->user) {
            if (! in_array(AuthenticationLoggable::class, class_uses_recursive(get_class($event->user)))) {
                return;
            }

            $user = $event->user;

            if (config('authentication-log.behind_cdn')) {
                $ip = $this->request->server(config('authentication-log.behind_cdn.http_header_field'));
            } else {
                $ip = $this->request->ip();
            }

            $userAgent = $this->request->userAgent();
            $log = $user->authentications()->whereIpAddress($ip)->whereUserAgent($userAgent)->orderByDesc('login_at')->first();

            if (! $log) {
                $log = $user->authentications()->create([
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                ]);
            }

            $log->logout_at = now();

            $user->authentications()->save($log);
        }
    }
}
