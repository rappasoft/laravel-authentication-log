<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;

class LoginListener
{
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle($event): void
    {
        $listener = config('authentication-log.events.login', Login::class);

        if (! $event instanceof $listener) {
            return;
        }

        if ($event->user) {
            if (! in_array(AuthenticationLoggable::class, class_uses_recursive(get_class($event->user)))) {
                return;
            }

            if (config('authentication-log.behind_cdn')) {
                $ip = $this->request->server(config('authentication-log.behind_cdn.http_header_field'));
            } else {
                $ip = $this->request->ip();
            }

            $user = $event->user;
            $userAgent = $this->request->userAgent();

            $log = $user->authentications()->create([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'login_at' => now(),
                'login_successful' => true,
                'location' => config('authentication-log.notifications.new-device.location') ? optional(geoip()->getLocation($ip))->toArray() : null,
            ]);

            if (empty(config('authentication-log.notifications.new-device.enabled'))) {
                return;
            }

            $known = $user->authentications()->whereIpAddress($ip)->whereUserAgent($userAgent)->whereLoginSuccessful(true)->first();
            $newUser = Carbon::parse($user->{$user->getCreatedAtColumn()})->diffInMinutes(Carbon::now()) < config('authentication-log.notifications.new-device.new_user_in_minutes', 1);

            if (! $known && ! $newUser) {
                $newDevice = config('authentication-log.notifications.new-device.template') ?? NewDevice::class;
                $user->notify(new $newDevice($log));
            }
        }
    }
}
