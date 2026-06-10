![Package Logo](https://banners.beyondco.de/Laravel%20Authentication%20Log.png?theme=dark&packageManager=composer+require&packageName=rappasoft%2Flaravel-authentication-log&pattern=hideout&style=style_1&description=Log+user+authentication+details+and+send+new+device+notifications.&md=1&showWatermark=0&fontSize=100px&images=lock-closed)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rappasoft/laravel-authentication-log.svg?style=flat-square)](https://packagist.org/packages/rappasoft/laravel-authentication-log)
[![Total Downloads](https://img.shields.io/packagist/dt/rappasoft/laravel-authentication-log.svg?style=flat-square)](https://packagist.org/packages/rappasoft/laravel-authentication-log)

Laravel Authentication Log is a comprehensive package which tracks your user's authentication information such as login/logout time, IP, Browser, Location, Device Fingerprint, etc. It sends out notifications via mail, slack, or SMS for new devices and failed logins, detects suspicious activity, provides session management, prevents duplicate log entries from session restorations, and much more.

**Version 6.0.0** introduces major enhancements including session restoration prevention, improved device fingerprinting, enhanced statistics, and more. See the [Release Notes](RELEASE_NOTES.md) for complete details.

## Features

### Core Features
- ✅ **Authentication Logging** - Tracks all login/logout attempts with IP, user agent, location, and timestamps
- ✅ **Device Fingerprinting** - Reliable device identification using SHA-256 hashing with browser version normalization (prevents false positives)
- ✅ **New Device Detection** - Automatically detects and notifies users of new device logins
- ✅ **Failed Login Tracking** - Logs and optionally notifies users of failed login attempts
- ✅ **Location Tracking** - Optional GeoIP integration for location data
- ✅ **Session Restoration Prevention** - Automatically prevents duplicate log entries from page refreshes and remember me cookies

### Advanced Features
- 🔒 **Suspicious Activity Detection** - Automatically detects multiple failed logins, rapid location changes, and unusual login times
- 📊 **Statistics & Insights** - Get comprehensive login statistics including total logins, failed attempts, unique devices, and more
- 🔐 **Session Management** - View active sessions, revoke specific sessions, or logout all other devices
- 🛡️ **Device Trust Management** - Mark devices as trusted, manage device names, and require trusted devices for sensitive actions
- ⚡ **Rate Limiting** - Prevents notification spam with configurable rate limits
- 🔔 **Webhook Support** - Send webhooks to external services for authentication events
- 📤 **Export Functionality** - Export authentication logs to CSV or JSON format
- 🎯 **Query Scopes** - Powerful query scopes for filtering logs (successful, failed, suspicious, recent, by IP, by device, etc.)
- 🚦 **Middleware** - Protect routes with trusted device middleware

## Documentation, Installation, and Usage Instructions

See the [documentation](https://rappasoft.com/docs/laravel-authentication-log) for detailed installation and usage instructions.

## Version Compatibility

 Laravel  | Authentication Log | Features
:---------|:------------------|:--------
 8.x      | 1.x               | Basic logging only
 9.x      | 2.x               | Basic logging only
 10.x     | 3.x               | Basic logging only
 11.x     | 5.x, 6.x          | All features (device fingerprinting, suspicious activity, webhooks, session management, etc.)
 12.x     | 5.x, 6.x          | All features (device fingerprinting, suspicious activity, webhooks, session management, etc.)
 13.x     | 6.1+              | All features (device fingerprinting, suspicious activity, webhooks, session management, etc.)

**Note:** Version 6.1+ requires Laravel 11.x, 12.x, or 13.x and PHP 8.2+. Version 5.x also supports Laravel 11.x and 12.x. For Laravel 10.x support, please use version 3.x.

## Installation

```bash
composer require rappasoft/laravel-authentication-log
```

## Quick Start

### 1. Add the Trait to Your User Model

```php
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;

class User extends Authenticatable
{
    use AuthenticationLoggable;
}
```

### 2. Publish and Run Migrations

**For new installations:**
```bash
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-migrations"
php artisan migrate
```

**For existing installations (upgrading from v5.x or earlier):**
```bash
# Update the package
composer update rappasoft/laravel-authentication-log

# Publish the upgrade migration (if upgrading from v3.x or earlier)
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-migrations"

# Run the migrations (the upgrade migration will only add columns if they don't exist)
php artisan migrate
```

**Important:** If upgrading from v3.x or earlier, the upgrade migration will safely add the new columns (`device_id`, `device_name`, `is_trusted`, `last_activity_at`, `is_suspicious`, `suspicious_reason`) to your existing `authentication_log` table without affecting existing data.

**Breaking Changes in v6.0.0:**
- Laravel 10.x support has been dropped (only Laravel 11.x and 12.x are supported)
- PHP 8.1+ is now required
- See the [Upgrade Guide](docs/start/upgrade.md) for detailed migration instructions

### 3. Configure (Optional)

```bash
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-config"
```

## Usage Examples

### Get User Statistics

```php
$user = User::find(1);

// Get comprehensive statistics
$stats = $user->getLoginStats();
// Returns: total_logins, failed_attempts, unique_devices, unique_ips, last_30_days, etc.

// Or get individual stats
$totalLogins = $user->getTotalLogins();
$failedAttempts = $user->getFailedAttempts();
$uniqueDevices = $user->getUniqueDevicesCount();
```

### Session Management

```php
// Get all active sessions
$activeSessions = $user->getActiveSessions();
$sessionCount = $user->getActiveSessionsCount();

// Revoke a specific session
$user->revokeSession($sessionId);

// Revoke all other sessions (keep current device)
$user->revokeAllOtherSessions($currentDeviceId);

// Revoke all sessions
$user->revokeAllSessions();
```

### Device Management

```php
// Get all user devices
$devices = $user->getDevices();

// Trust a device
$user->trustDevice($deviceId);

// Untrust a device
$user->untrustDevice($deviceId);

// Update device name
$user->updateDeviceName($deviceId, 'My iPhone');

// Check if device is trusted
if ($user->isDeviceTrusted($deviceId)) {
    // Device is trusted
}
```

### Query Scopes

```php
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

// Filter successful logins
$successfulLogins = AuthenticationLog::successful()->get();

// Filter failed logins
$failedLogins = AuthenticationLog::failed()->get();

// Filter by IP address
$ipLogs = AuthenticationLog::fromIp('192.168.1.1')->get();

// Filter recent logs (last 7 days)
$recentLogs = AuthenticationLog::recent(7)->get();

// Filter suspicious activities
$suspicious = AuthenticationLog::suspicious()->get();

// Filter active sessions
$activeSessions = AuthenticationLog::active()->get();

// Filter trusted devices
$trustedDevices = AuthenticationLog::trusted()->get();

// Filter by device ID
$deviceLogs = AuthenticationLog::fromDevice($deviceId)->get();

// Filter for specific user
$userLogs = AuthenticationLog::forUser($user)->get();
```

### Suspicious Activity Detection

```php
// Detect suspicious activity
$suspiciousActivities = $user->detectSuspiciousActivity();

// Returns array of suspicious activities:
// [
//     [
//         'type' => 'multiple_failed_logins',
//         'count' => 5,
//         'message' => '5 failed login attempts in the last hour'
//     ],
//     [
//         'type' => 'rapid_location_change',
//         'countries' => ['US', 'UK'],
//         'message' => 'Login from multiple countries within an hour'
//     ]
// ]
```

### Middleware for Trusted Devices

```php
use Rappasoft\LaravelAuthenticationLog\Middleware\RequireTrustedDevice;

// In your routes file
Route::middleware(['auth', RequireTrustedDevice::class])->group(function () {
    // These routes require a trusted device
    Route::get('/sensitive-action', [Controller::class, 'sensitiveAction']);
});
```

### Export Logs

```bash
# Export all logs to CSV
php artisan authentication-log:export --format=csv

# Export to JSON
php artisan authentication-log:export --format=json

# Specify custom output path
php artisan authentication-log:export --format=csv --path=storage/app/logs.csv
```

### Webhook Configuration

Add webhooks to your `config/authentication-log.php`:

```php
'webhooks' => [
    [
        'url' => 'https://example.com/webhook',
        'events' => ['login', 'failed', 'new_device', 'suspicious'],
        'headers' => [
            'Authorization' => 'Bearer your-token',
        ],
    ],
],
```

## Configuration

The package includes comprehensive configuration options:

- **Notifications** - Configure new device and failed login notifications with rate limiting
- **Suspicious Activity** - Configure thresholds and detection rules
- **Webhooks** - Set up webhook endpoints for external integrations
- **Database** - Customize table name and database connection
- **Session Restoration** - Configure session restoration prevention (prevents duplicate log entries)
- **New User Threshold** - Configure time window for new user detection

See the [configuration documentation](https://rappasoft.com/docs/laravel-authentication-log/start/configuration) for all available options.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Anthony Rappa](https://github.com/rappasoft)
- [yadahan/laravel-authentication-log](https://github.com/yadahan/laravel-authentication-log)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
