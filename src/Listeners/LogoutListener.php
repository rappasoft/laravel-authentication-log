<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Support\Utils;

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

        if (! Utils::hasAuthenticationLoggableContract($event)) {
            return;
        }

        $user = $event->user;
        $ip = $this->request->ip();
        $userAgent = $this->request->userAgent();
        $log = $user->authentications()->whereIpAddress($ip)->whereUserAgent($userAgent)->orderByDesc('login_at')->first();

        if (! $log) {
            $log = new AuthenticationLog([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
        }

        $log->logout_at = now();

        $user->authentications()->save($log);
    }
}
