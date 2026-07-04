<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint;
use Rappasoft\LaravelAuthenticationLog\Helpers\NotificationRateLimiter;
use Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice;
use Rappasoft\LaravelAuthenticationLog\Notifications\SuspiciousActivity;
use Rappasoft\LaravelAuthenticationLog\Services\WebhookService;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;

class LoginListener
{
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(Login $event): void
    {
        if ($event->user instanceof Authenticatable) {
            /** @var Authenticatable&\Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable $user */
            $user = $event->user;

            if (! in_array(AuthenticationLoggable::class, class_uses_recursive(get_class($user)))) {
                return;
            }

            if (config('authentication-log.behind_cdn')) {
                $ip = $this->request->server(config('authentication-log.behind_cdn.http_header_field'));
            } else {
                $ip = $this->request->ip();
            }

            $userAgent = $this->request->userAgent();
            $deviceId = DeviceFingerprint::generate($this->request);
            $deviceName = DeviceFingerprint::generateDeviceName($this->request);

            // Check if device is known (by successful login)
            $known = $user->authentications()->fromDevice($deviceId)->successful()->first();

            // Check if there was a failed login on this device (security concern)
            $hadFailedLoginOnDevice = $user->authentications()
                ->fromDevice($deviceId)
                ->failed()
                ->where('login_at', '>', now()->subHours(24)) // Within last 24 hours
                ->exists();

            $newUserThreshold = config('authentication-log.notifications.new-device.new_user_threshold_minutes', 1);
            $newUser = Carbon::parse($user->{$user->getCreatedAtColumn()})->diffInMinutes(Carbon::now()) < $newUserThreshold;

            // Check if this is a session restoration (not a real login)
            // Laravel fires Login event on session restoration (page refresh, remember me cookie)
            $isSessionRestoration = false;
            if (config('authentication-log.prevent_session_restoration_logging', true)) {
                $restorationWindow = config('authentication-log.session_restoration_window_minutes', 5);
                $restorationCutoff = now()->subMinutes($restorationWindow);
                // Measure the window against the session's most recent activity, not its
                // original login_at. last_activity_at is bumped on every restoration below,
                // so anchoring the lookup on login_at made a continuously-active session
                // "age out" of the window and spawn a brand-new log row every
                // $restorationWindow minutes for as long as the user stayed active.
                $existingActiveSession = $user->authentications()
                    ->fromDevice($deviceId)
                    ->successful()
                    ->whereNull('logout_at')
                    ->where(function ($query) use ($restorationCutoff) {
                        $query->where('last_activity_at', '>', $restorationCutoff)
                            ->orWhere(function ($query) use ($restorationCutoff) {
                                $query->whereNull('last_activity_at')
                                    ->where('login_at', '>', $restorationCutoff);
                            });
                    })
                    ->orderByDesc('last_activity_at')
                    ->orderByDesc('login_at')
                    ->first();

                if ($existingActiveSession) {
                    // This is a session restoration, update last_activity_at instead of creating new entry
                    $existingActiveSession->update(['last_activity_at' => now()]);
                    $isSessionRestoration = true;
                }
            }

            // If this is a session restoration, skip creating a new log entry
            if ($isSessionRestoration) {
                return;
            }

            // Detect suspicious activity
            $suspiciousActivities = $user->detectSuspiciousActivity();
            $isSuspicious = ! empty($suspiciousActivities);
            $suspiciousReason = $isSuspicious ? json_encode($suspiciousActivities) : null;

            $log = $user->authentications()->create([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'is_trusted' => $known?->is_trusted ?? false,
                'login_at' => now(),
                'login_successful' => true,
                'last_activity_at' => now(),
                'location' => config('authentication-log.notifications.new-device.location') && function_exists('geoip') ? (geoip()->getLocation($ip)?->toArray()) : null,
                'is_suspicious' => $isSuspicious,
                'suspicious_reason' => $suspiciousReason,
            ]);

            // Mark as suspicious if detected
            if ($isSuspicious) {
                $log->markAsSuspicious($suspiciousReason ?? 'Suspicious activity detected');
            }

            // Send new device notification with rate limiting
            // Send if: device is unknown OR there was a failed login on this device (security concern)
            $shouldNotify = (! $known || $hadFailedLoginOnDevice) && ! $newUser;

            if ($shouldNotify && config('authentication-log.notifications.new-device.enabled')) {
                $rateLimitKey = "new_device:{$user->id}";
                $maxAttempts = config('authentication-log.notifications.new-device.rate_limit', 3);
                $decayMinutes = config('authentication-log.notifications.new-device.rate_limit_decay', 60);

                if (NotificationRateLimiter::shouldSend($rateLimitKey, $maxAttempts, $decayMinutes)) {
                    $newDeviceClass = config('authentication-log.notifications.new-device.template') ?? NewDevice::class;
                    /** @var Authenticatable&\Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable $user */
                    $user->notify(new $newDeviceClass($log));
                }
            }

            // Send suspicious activity notification with rate limiting
            if ($isSuspicious && config('authentication-log.notifications.suspicious-activity.enabled')) {
                $rateLimitKey = "suspicious_activity:{$user->id}";
                $maxAttempts = config('authentication-log.notifications.suspicious-activity.rate_limit', 3);
                $decayMinutes = config('authentication-log.notifications.suspicious-activity.rate_limit_decay', 60);

                if (NotificationRateLimiter::shouldSend($rateLimitKey, $maxAttempts, $decayMinutes)) {
                    $suspiciousActivityClass = config('authentication-log.notifications.suspicious-activity.template') ?? SuspiciousActivity::class;
                    /** @var Authenticatable&\Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable $user */
                    $user->notify(new $suspiciousActivityClass($log, $suspiciousActivities));
                }
            }

            // Send webhooks
            WebhookService::send('login', $log, $user);

            if ($isSuspicious) {
                WebhookService::send('suspicious', $log, $user);
            }

            if ((! $known || $hadFailedLoginOnDevice) && ! $newUser) {
                WebhookService::send('new_device', $log, $user);
            }
        }
    }
}
