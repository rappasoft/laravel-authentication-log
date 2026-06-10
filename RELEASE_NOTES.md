# Laravel Authentication Log Release Notes

# v6.1.0 Release Notes

## 🚀 Laravel 13 Support & Immutable Date Compatibility

Released 2026-06-10.

- **Laravel 13.x support** ([#140](https://github.com/rappasoft/laravel-authentication-log/pull/140)) — the package and CI matrix now cover PHP 8.2–8.4 on Laravel 11.x, 12.x, and 13.x
- **Immutable date casting compatibility** ([#137](https://github.com/rappasoft/laravel-authentication-log/pull/137)) — `lastLoginAt()`, `lastSuccessfulLoginAt()`, and `previousLoginAt()` now return `\Carbon\CarbonInterface` instead of `\Illuminate\Support\Carbon`, fixing a `TypeError` in applications that cast model dates to `CarbonImmutable` (the default in current Laravel starter kits)
- **PHP 8.2+ now required** — PHP 8.1 is EOL and was never installable alongside Laravel 11+, so this does not affect any working installation

No database changes or migrations are required when upgrading from v6.0.x.

---

# v6.0.0 Release Notes

## 🎉 Major Release - Enhanced Features & Modernization

This is a major release that modernizes the package for Laravel 11.x and 12.x, adds numerous new features, and fixes several long-standing issues.

## ⚠️ Breaking Changes

- **Laravel 10.x support dropped**: This package now only supports Laravel 11.x and 12.x (Laravel 12 support was added in v5.0.0)
- **PHP 8.1+ required**: Minimum PHP version is now 8.1
- **Database migration required**: Existing installations must run the upgrade migration to add new columns

## 🚀 New Features

### 1. Suspicious Activity Detection
Automatically detect and flag suspicious login patterns including:
- Multiple failed login attempts
- Rapid location changes
- Unusual login times (configurable)

**Configuration:**
```php
'suspicious' => [
    'failed_login_threshold' => 5,
    'check_unusual_times' => false,
    'usual_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
],
```

### 2. Session Management
Comprehensive session management capabilities:
- View active sessions
- Revoke specific sessions
- Revoke all other sessions (keep current device)
- Revoke all sessions
- Track last activity timestamp

**Usage:**
```php
$user->getActiveSessions();
$user->revokeSession($sessionId);
$user->revokeAllOtherSessions($currentDeviceId);
$user->revokeAllSessions();
```

### 3. Device Fingerprinting & Management
- Unique device identification (normalized user agent to prevent false positives)
- Device trust management
- Device naming
- Browser version normalization (prevents false "new device" notifications)

**Usage:**
```php
$user->getDevices();
$user->trustDevice($deviceId);
$user->untrustDevice($deviceId);
$user->isDeviceTrusted($deviceId);
```

### 4. Query Scopes
Powerful query scopes for filtering authentication logs:
- `successful()` - Only successful logins
- `failed()` - Only failed attempts
- `fromIp($ip)` - Filter by IP address
- `recent($hours)` - Recent logs
- `suspicious()` - Suspicious activities
- `trusted()` - Trusted devices only
- `fromDevice($deviceId)` - Specific device
- `forUser($user)` - Specific user
- `active()` - Active sessions

**Usage:**
```php
AuthenticationLog::suspicious()->recent(24)->get();
$user->authentications()->failed()->recent(1)->count();
```

### 5. Statistics & Insights
Get authentication statistics for users:
- Total logins count
- Failed attempts count
- Unique devices count
- Suspicious activities count
- Comprehensive login stats array

**Usage:**
```php
$stats = $user->getLoginStats();
$totalLogins = $user->getTotalLogins();
$failedAttempts = $user->getFailedAttempts();
$uniqueDevices = $user->getUniqueDevicesCount();
```

### 6. Rate Limiting for Notifications
Prevent notification spam with configurable rate limiting:
- Configurable max attempts per time period
- Separate limits for new device and failed login notifications
- Automatic rate limit decay

**Configuration:**
```php
'new-device' => [
    'rate_limit' => 3,
    'rate_limit_decay' => 60, // minutes
],
```

### 7. Middleware for Device Trust
Restrict access to trusted devices only:

**Usage:**
```php
Route::middleware(['auth', \Rappasoft\LaravelAuthenticationLog\Middleware\RequireTrustedDevice::class])
    ->group(function () {
        // Protected routes
    });
```

### 8. Export Functionality
Export authentication logs to CSV or JSON:

**Usage:**
```bash
php artisan authentication-log:export --format=csv --path=storage/app/logs.csv
php artisan authentication-log:export --format=json
```

### 9. Webhook Support
Send webhooks for authentication events:
- Login events
- Failed login events
- New device events
- Suspicious activity events

**Configuration:**
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

### 10. Enhanced Notifications
- Support for Vonage (formerly Nexmo) SMS notifications
- Custom notification templates
- Improved email templates with better error handling

### 11. Configurable New User Threshold
Prevent false positives for new users connecting from multiple devices/locations:

**Configuration:**
```php
'new-device' => [
    'new_user_threshold_minutes' => 1, // Default: 1 minute
],
```

### 12. Session Restoration Prevention
**Fixes [#13](https://github.com/rappasoft/laravel-authentication-log/issues/13)**

Automatically prevents session restorations (page refreshes, remember me cookies) from creating duplicate log entries. Updates `last_activity_at` instead of creating new entries.

**Configuration:**
```php
'prevent_session_restoration_logging' => true,
'session_restoration_window_minutes' => 5,
```

## 🐛 Bug Fixes

### Fixed Issue #40 - Browser Version Updates Triggering False Notifications
**Fixes [#40](https://github.com/rappasoft/laravel-authentication-log/issues/40)**

Browser version updates (e.g., Safari 14.1.2 → 15.1) no longer trigger false "new device" notifications. Device fingerprinting now normalizes user agent strings by removing version numbers.

### Fixed Issue #13 - Session Restoration Logging
**Fixes [#13](https://github.com/rappasoft/laravel-authentication-log/issues/13)**

Session restorations (page refreshes, remember me cookies) no longer create duplicate log entries. The package now detects and handles session restorations automatically.

### Fixed Issue #48, #87, #111 - SQL Server Duplicate ORDER BY Error
**Fixes [#48](https://github.com/rappasoft/laravel-authentication-log/issues/48), [#87](https://github.com/rappasoft/laravel-authentication-log/issues/87), [#111](https://github.com/rappasoft/laravel-authentication-log/issues/111)**

Fixed SQL Server error "A column has been specified more than once in the order by list" by removing duplicate `orderByDesc('login_at')` calls. The `authentications()` relationship already orders by `login_at DESC`, so additional ordering was unnecessary.

### Fixed Issue #33, #58 - Model Exception for Models Without Trait
**Fixes [#33](https://github.com/rappasoft/laravel-authentication-log/issues/33), [#58](https://github.com/rappasoft/laravel-authentication-log/issues/58)**

All listeners now check if the authenticatable model implements the `AuthenticationLoggable` trait before processing, preventing `BadMethodCallException` errors when using multiple authenticatable models where only some have the trait.

### Fixed Issue #82 - Duplicate Log Entries
**Fixes [#82](https://github.com/rappasoft/laravel-authentication-log/issues/82)**

Duplicate log entries issue resolved by session restoration prevention (same fix as Issue #13).

## ✅ Pull Requests Implemented

### PR #15 - Notification After Failed Login on New Device
**Closes [#15](https://github.com/rappasoft/laravel-authentication-log/pull/15)**

The package now sends new device notifications when a successful login occurs after a failed login attempt on an unknown device.

### PR #52 - Optimize Other Devices Logout Listener
**Closes [#52](https://github.com/rappasoft/laravel-authentication-log/pull/52)**

Already implemented. The listener filters to only active sessions using `whereNull('logout_at')`.

### PR #57 - Use Null Safe/Chaining Operator
**Closes [#57](https://github.com/rappasoft/laravel-authentication-log/pull/57)**

Already implemented. The codebase uses null-safe operators (`?->`) instead of `optional()`.

### PR #80 - Added PHPDocs for IDE Autocompletion
**Closes [#80](https://github.com/rappasoft/laravel-authentication-log/pull/80)**

Already implemented. The `AuthenticationLog` model includes PHPDoc comments for all properties including new fields.

### PR #85 - Configurable New User Threshold
**Closes [#85](https://github.com/rappasoft/laravel-authentication-log/pull/85)**

Added `new_user_threshold_minutes` configuration option to reduce false positives for users connecting from multiple devices/locations shortly after registration.

### PR #92 - Configurable Listeners
**Closes [#92](https://github.com/rappasoft/laravel-authentication-log/pull/92)**

Already implemented. The config file includes configurable listeners for all authentication events.

### PR #94 - Check Trait Implementation
**Closes [#94](https://github.com/rappasoft/laravel-authentication-log/pull/94)**

Already implemented. All listeners check if the user model implements the `AuthenticationLoggable` trait before processing.

### PR #100 - Laravel 11 Support
**Closes [#100](https://github.com/rappasoft/laravel-authentication-log/pull/100)**

Package now supports Laravel 11.x and 12.x.

### PR #115 - Check if GeoIP is Installed
**Closes [#115](https://github.com/rappasoft/laravel-authentication-log/pull/115)**

Config defaults now check if geoip function exists before enabling location tracking, preventing errors when the geoip package is not installed.

### PR #120 - Laravel 12 Support & Arabic Translation
**Closes [#120](https://github.com/rappasoft/laravel-authentication-log/pull/120)**

Laravel 12 support added and Arabic translation (`ar.json`) included.

### PR #125 - Test Configuration Updates
**Closes [#125](https://github.com/rappasoft/laravel-authentication-log/pull/125)**

Test configuration updated for Laravel 11+ support.

### PR #127 - Spanish Translation & Blade Fixes
**Closes [#127](https://github.com/rappasoft/laravel-authentication-log/pull/127)**

Spanish translation (`es_ES.json`) exists and blade templates use the null coalescing operator (`??`) for state/country fields.

## 📝 Pull Requests No Longer Applicable

### PR #70 - Laravel 10 Support
**Closes [#70](https://github.com/rappasoft/laravel-authentication-log/pull/70)**

No longer applicable. Package v4.0.0 dropped Laravel 10 support and now only supports Laravel 11.x and 12.x.

## 📚 Documentation

- Comprehensive upgrade guide added
- All new features documented
- Configuration examples updated
- Usage examples for all new features

## 🧪 Testing

- **76 tests passing** (146 assertions)
- Comprehensive test coverage for all new features
- Tests for session restoration prevention
- Tests for device fingerprinting normalization
- Tests for suspicious activity detection
- Tests for all query scopes and statistics

## 📦 Installation & Upgrade

### New Installation
```bash
composer require rappasoft/laravel-authentication-log
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider"
php artisan migrate
```

### Upgrading from v5.x or Earlier
```bash
composer update rappasoft/laravel-authentication-log
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-migrations"
php artisan migrate
```

The upgrade migration will safely add new columns to your existing `authentication_log` table without data loss.

## 🙏 Credits

Thank you to all contributors who submitted issues, pull requests, and feedback that made this release possible!

## 📖 Full Documentation

See the [documentation](https://rappasoft.com/docs/laravel-authentication-log) for complete usage instructions and examples.

---

**Note:** This release includes breaking changes. Please review the upgrade guide before upgrading from v5.x or earlier.

