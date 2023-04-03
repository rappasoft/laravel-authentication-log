<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\OtherDeviceLogout;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use WhichBrowser\Parser;

class OtherDeviceLogoutListener extends EventListener
{
    public function handle($event): void
    {
        if (! $this->isListenerForEvent($event, 'other-device-logout', OtherDeviceLogout::class)) {
            return;
        }

        if (! $this->isLoggable($event)) {
            return;
        }

        $user = $event->user;

        $authenticationLog = $this->getKnownDevices($user);

        if (! $authenticationLog) {
            $authenticationLog = new AuthenticationLog([
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ]);
        }

        $user
            ->authentications()
            ->where('login_successful', true)
            ->whereNull('logout_at')
            ->where('id', '!=', $authenticationLog->id)
            ->update([
                'cleared_by_user' => true,
                'logout_at' => now(),
            ]);
    }

    protected function getKnownDevices($user): ?AuthenticationLog
    {
        $parser = new Parser($this->request->userAgent());

        return $user->authentications()
            ->where('ip_address', $this->request->ip())
            ->where('browser', $parser->browser->name)
            ->where('browser_os', $parser->os->name)
            ->first();
    }
}
