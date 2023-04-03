<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice;
use WhichBrowser\Parser;

class LoginListener extends EventListener
{
    public function handle($event): void
    {
        if (! $this->isListenerForEvent($event, 'login', Login::class)) {
            return;
        }

        if (! $this->isLoggable($event)) {
            return;
        }

        $user = $event->user;
        $newUser = Carbon::parse($user->{$user->getCreatedAtColumn()})->diffInMinutes(Carbon::now()) < 1;

        $log = $user->authentications()->create([
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'login_at' => now(),
            'login_successful' => true,
            'location' => config('authentication-log.notifications.new-device.location') ? optional(geoip()->getLocation($this->request->ip()))->toArray() : null,
        ]);

        if (! $newUser && config('authentication-log.notifications.new-device.enabled') && $this->getKnownDevices($user)) {
            $newDevice = config('authentication-log.notifications.new-device.template') ?? NewDevice::class;
            $user->notify(new $newDevice($log));
        }
    }

    protected function getKnownDevices($user): ?AuthenticationLog
    {
        $parser = new Parser($this->request->userAgent());

        return $user->authentications()
            ->where('ip_address', $this->request->ip())
            ->where('browser', $parser->browser->name)
            ->where('browser_os', $parser->os->name)
            ->where('login_successful', true)
            ->first();
    }
}
