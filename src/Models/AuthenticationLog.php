<?php

namespace Rappasoft\LaravelAuthenticationLog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $authenticatable_type
 * @property int $authenticatable_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $device_id
 * @property string|null $device_name
 * @property bool $is_trusted
 * @property \Carbon\CarbonInterface|null $login_at
 * @property bool $login_successful
 * @property \Carbon\CarbonInterface|null $logout_at
 * @property \Carbon\CarbonInterface|null $last_activity_at
 * @property bool $cleared_by_user
 * @property array|null $location
 * @property bool $is_suspicious
 * @property string|null $suspicious_reason
 */
class AuthenticationLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'authentication_log';

    protected $fillable = [
        'ip_address',
        'user_agent',
        'device_id',
        'device_name',
        'is_trusted',
        'login_at',
        'login_successful',
        'logout_at',
        'last_activity_at',
        'cleared_by_user',
        'location',
        'is_suspicious',
        'suspicious_reason',
    ];

    protected $casts = [
        'cleared_by_user' => 'boolean',
        'is_trusted' => 'boolean',
        'is_suspicious' => 'boolean',
        'location' => 'array',
        'login_successful' => 'boolean',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    protected $attributes = [
        'login_successful' => false,
        'cleared_by_user' => false,
        'is_trusted' => false,
        'is_suspicious' => false,
    ];

    public function __construct(array $attributes = [])
    {
        if (! isset($this->connection)) {
            $this->setConnection(config('authentication-log.db_connection'));
        }

        parent::__construct($attributes);
    }

    public function getTable()
    {
        return config('authentication-log.table_name', parent::getTable());
    }

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    // Query Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('login_successful', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('login_successful', false);
    }

    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('login_at', '>=', now()->subDays($days));
    }

    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    public function scopeActive($query)
    {
        return $query->where('login_successful', true)
            ->whereNull('logout_at');
    }

    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    public function scopeFromDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeForUser($query, $user)
    {
        return $query->where('authenticatable_type', get_class($user))
            ->where('authenticatable_id', $user->id);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->login_successful && $this->logout_at === null;
    }

    public function markAsSuspicious(string $reason): void
    {
        $this->update([
            'is_suspicious' => true,
            'suspicious_reason' => $reason,
        ]);
    }

    public function markAsTrusted(): void
    {
        $this->update(['is_trusted' => true]);
    }

    public function markAsUntrusted(): void
    {
        $this->update(['is_trusted' => false]);
    }

    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
}
