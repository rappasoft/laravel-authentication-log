<?php

namespace Rappasoft\LaravelAuthenticationLog\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

trait AuthenticationLoggable
{
    public function authentications(): MorphMany
    {
        return $this->morphMany(AuthenticationLog::class, 'authenticatable')->latest('login_at');
    }

    public function latestAuthentication(): MorphOne
    {
        return $this->morphOne(AuthenticationLog::class, 'authenticatable')->latestOfMany('login_at');
    }

    public function notifyAuthenticationLogVia(): array
    {
        return ['mail'];
    }

    public function lastLoginAt(): ?\Carbon\CarbonInterface
    {
        return $this->authentications()->first()?->login_at;
    }

    public function lastSuccessfulLoginAt(): ?\Carbon\CarbonInterface
    {
        return $this->authentications()->whereLoginSuccessful(true)->first()?->login_at;
    }

    public function lastLoginIp(): ?string
    {
        return $this->authentications()->first()?->ip_address;
    }

    public function lastSuccessfulLoginIp(): ?string
    {
        return $this->authentications()->whereLoginSuccessful(true)->first()?->ip_address;
    }

    public function previousLoginAt(): ?\Carbon\CarbonInterface
    {
        return $this->authentications()->skip(1)->first()?->login_at;
    }

    public function previousLoginIp(): ?string
    {
        return $this->authentications()->skip(1)->first()?->ip_address;
    }

    // Statistics Methods
    public function getLoginStats(): array
    {
        return [
            'total_logins' => $this->authentications()->successful()->count(),
            'failed_attempts' => $this->authentications()->failed()->count(),
            'unique_devices' => $this->authentications()->successful()->distinct()->count('device_id'),
            'unique_ips' => $this->authentications()->successful()->distinct()->count('ip_address'),
            'last_30_days' => $this->authentications()->successful()->recent(30)->count(),
            'last_7_days' => $this->authentications()->successful()->recent(7)->count(),
            'suspicious_activities' => $this->authentications()->suspicious()->count(),
            'trusted_devices' => $this->authentications()->trusted()->count(),
        ];
    }

    public function getTotalLogins(): int
    {
        return $this->authentications()->successful()->count();
    }

    public function getFailedAttempts(): int
    {
        return $this->authentications()->failed()->count();
    }

    public function getUniqueDevicesCount(): int
    {
        return $this->authentications()->successful()->distinct()->count('device_id');
    }

    public function getSuspiciousActivitiesCount(): int
    {
        return $this->authentications()->suspicious()->count();
    }

    // Session Management Methods
    public function activeSessions()
    {
        return $this->authentications()->active()->latest('login_at');
    }

    public function getActiveSessions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeSessions()->get();
    }

    public function getActiveSessionsCount(): int
    {
        return $this->activeSessions()->count();
    }

    public function revokeSession(int $sessionId): bool
    {
        $session = $this->authentications()->find($sessionId);

        if (! $session || ! $session->isActive()) {
            return false;
        }

        return $session->update([
            'logout_at' => now(),
            'cleared_by_user' => true,
        ]);
    }

    public function revokeAllOtherSessions(?string $currentDeviceId = null): int
    {
        $query = $this->authentications()->active();

        if ($currentDeviceId) {
            $query->where('device_id', '!=', $currentDeviceId);
        }

        return $query->update([
            'logout_at' => now(),
            'cleared_by_user' => true,
        ]);
    }

    public function revokeAllSessions(): int
    {
        return $this->authentications()->active()->update([
            'logout_at' => now(),
            'cleared_by_user' => true,
        ]);
    }

    // Device Management Methods
    public function getDevices(): \Illuminate\Database\Eloquent\Collection
    {
        // Get distinct devices by selecting the most recent entry for each device_id
        $table = (new \Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog())->getTable();

        return $this->authentications()
            ->successful()
            ->select('device_id', 'device_name', 'ip_address', 'user_agent', 'is_trusted', 'login_at')
            ->whereNotNull('device_id')
            ->whereIn('id', function ($query) use ($table) {
                $query->selectRaw('MAX(id)')
                    ->from($table)
                    ->where('authenticatable_type', get_class($this))
                    ->where('authenticatable_id', $this->id)
                    ->where('login_successful', true)
                    ->whereNotNull('device_id')
                    ->groupBy('device_id');
            })
            ->orderBy('login_at', 'desc')
            ->get();
    }

    public function trustDevice(string $deviceId): bool
    {
        return $this->authentications()
            ->fromDevice($deviceId)
            ->update(['is_trusted' => true]);
    }

    public function untrustDevice(string $deviceId): bool
    {
        return $this->authentications()
            ->fromDevice($deviceId)
            ->update(['is_trusted' => false]);
    }

    public function updateDeviceName(string $deviceId, string $name): bool
    {
        return $this->authentications()
            ->fromDevice($deviceId)
            ->update(['device_name' => $name]);
    }

    public function isDeviceTrusted(string $deviceId): bool
    {
        return $this->authentications()
            ->fromDevice($deviceId)
            ->trusted()
            ->exists();
    }

    // Suspicious Activity Detection
    public function detectSuspiciousActivity(): array
    {
        $suspicious = [];

        // Check for multiple failed logins (in the last hour)
        $recentFailed = $this->authentications()
            ->failed()
            ->where('login_at', '>=', now()->subHour())
            ->count();

        if ($recentFailed >= config('authentication-log.suspicious.failed_login_threshold', 5)) {
            $suspicious[] = [
                'type' => 'multiple_failed_logins',
                'count' => $recentFailed,
                'message' => "{$recentFailed} failed login attempts in the last hour",
            ];
        }

        // Check for rapid location changes (in the last hour)
        $recentLogins = $this->authentications()
            ->successful()
            ->where('login_at', '>=', now()->subHour())
            ->whereNotNull('location')
            ->get();

        if ($recentLogins->count() >= 2) {
            $countries = $recentLogins->pluck('location.country')->filter()->unique();
            if ($countries->count() > 1) {
                $suspicious[] = [
                    'type' => 'rapid_location_change',
                    'countries' => $countries->toArray(),
                    'message' => 'Login from multiple countries within an hour',
                ];
            }
        }

        // Check for unusual login times (if configured)
        if (config('authentication-log.suspicious.check_unusual_times', false)) {
            $currentHour = now()->hour;
            $usualHours = config('authentication-log.suspicious.usual_hours', [9, 10, 11, 12, 13, 14, 15, 16, 17]);

            if (! in_array($currentHour, $usualHours)) {
                $suspicious[] = [
                    'type' => 'unusual_login_time',
                    'hour' => $currentHour,
                    'message' => "Login at unusual time: {$currentHour}:00",
                ];
            }
        }

        return $suspicious;
    }
}
