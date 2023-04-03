<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Logout;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use WhichBrowser\Parser;

class LogoutListener extends EventListener
{
    public function handle($event): void
    {
        if (! $this->isListenerForEvent($event, 'logout', Logout::class)) {
            return;
        }

        if (! $this->isLoggable($event)) {
            return;
        }

        $user = $event->user;
        $log = $this->getLatestLoginLog($user);

        if (! $log) {
            $log = new AuthenticationLog([
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ]);
        }

        $log->logout_at = now();

        $user->authentications()->save($log);
    }

    protected function getLatestLoginLog($user): ?AuthenticationLog
    {
        $parser = new Parser($this->request->userAgent());

        return $user->authentications()
            ->where('ip_address', $this->request->ip())
            ->where('browser', $parser->browser->name)
            ->where('browser_os', $parser->os->name)
            ->orderByDesc('login_at')
            ->first();
    }
}
