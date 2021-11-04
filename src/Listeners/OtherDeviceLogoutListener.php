<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Http\Request;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class OtherDeviceLogoutListener
{
    /**
     * The request.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(OtherDeviceLogout $event): void
    {
        if ($event->user) {
            $user = $event->user;
            $ip = $this->request->ip();
            $userAgent = $this->request->userAgent();
            $authenticationLog = $user->authentications()->whereIpAddress($ip)->whereUserAgent($userAgent)->first();

            if (! $authenticationLog) {
                $authenticationLog = new AuthenticationLog([
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                ]);
            }

            foreach ($user->authentications()->whereLoginSuccessful(true)->get() as $log) {
                if ($log->id !== $authenticationLog->id) {
                    $log->update([
                        'cleared_by_user' => true,
                        'logout_at' => now(),
                    ]);
                }
            }
        }
    }
}
