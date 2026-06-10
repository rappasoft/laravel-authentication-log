# Changelog

All notable changes to `Laravel Authentication Log` will be documented in this file.

### 6.1.0 - 2026-06-10

#### Added
- Laravel 13.x support (#138, #140)
- Regression test covering applications that use immutable date casting

#### Changed
- `lastLoginAt()`, `lastSuccessfulLoginAt()`, and `previousLoginAt()` now return `\Carbon\CarbonInterface` instead of `\Illuminate\Support\Carbon`, fixing a `TypeError` in applications that cast model dates to `CarbonImmutable` — the default in the current Laravel starter kits (#137)
- PHP 8.2+ is now required (PHP 8.1 is EOL, and no Laravel version supported by this package runs on it)
- Modernized the PHPUnit configuration for PHPUnit 10+ and updated the CI matrix (PHP 8.2–8.4, Laravel 11.x–13.x)

### 6.0.1 - 2025-12-08

#### Fixed
- The upgrade migration's `down()` method now respects the configured table name (#135, #136)

### 6.0.0 - 2025-01-27

#### Added
- Suspicious activity detection with configurable thresholds
- Comprehensive session management (view, revoke sessions)
- Device fingerprinting with browser version normalization
- Device trust management (trust/untrust devices)
- Query scopes for filtering authentication logs (successful, failed, suspicious, trusted, active, etc.)
- Statistics and insights methods (getLoginStats, getTotalLogins, etc.)
- Rate limiting for notifications (prevent spam)
- Middleware for device trust (RequireTrustedDevice)
- Export functionality (CSV/JSON export command)
- Webhook support for authentication events
- Configurable new user threshold (prevent false positives)
- Session restoration prevention (fixes duplicate log entries from page refreshes)
- Arabic translation (ar.json)
- Enhanced notifications with Vonage support

#### Fixed
- Issue #40: Browser version updates no longer trigger false "new device" notifications
- Issue #13: Session restorations no longer create duplicate log entries

#### Changed
- Dropped Laravel 10.x support (now only supports Laravel 11.x and 12.x)
- PHP 8.1+ required
- Config defaults now check for geoip function existence
- Improved device fingerprinting to normalize user agent strings

#### Implemented Pull Requests
- PR #15: Notification after failed login on new device
- PR #52: Optimize Other Devices Logout Listener
- PR #57: Use null safe/chaining operator
- PR #80: Added PHPDocs for IDE autocompletion
- PR #85: Configurable new user threshold
- PR #92: Configurable listeners
- PR #94: Check trait implementation
- PR #100: Laravel 11 support
- PR #115: Check if geoip is installed
- PR #120: Laravel 12 support & Arabic translation
- PR #125: Test configuration updates
- PR #127: Spanish translation & blade fixes

### 5.0.0 - 2024-XX-XX

- Laravel 12 Support

### 4.0.0 - 2024-03-28

- Laravel 11 Support (https://github.com/rappasoft/laravel-authentication-log/pull/100)
- Add config listeners (https://github.com/rappasoft/laravel-authentication-log/pull/92)
- Use real user IP behind Cloudflare
- Check for AuthenticationLoggable trait on event (https://github.com/rappasoft/laravel-authentication-log/pull/94)
- Added PHPDocs to allow autocompletion in IDE (https://github.com/rappasoft/laravel-authentication-log/pull/80)
- Fixes the down method for php artisan migrate:rollback (https://github.com/rappasoft/laravel-authentication-log/pull/93)

### 3.0.0 - 2023-02-23

- Laravel 10 Support - https://github.com/rappasoft/laravel-authentication-log/pull/70
- Use null safe/chaining operator - https://github.com/rappasoft/laravel-authentication-log/pull/57
- Optimize Other Devices Logout Listener - https://github.com/rappasoft/laravel-authentication-log/pull/52

### 2.0.0 - 2022-02-19

### Added

- Laravel 9 Support

### 1.3.0 - 2022-01-17

### Changed

-   Added missing `hasTranslations()` - https://github.com/rappasoft/laravel-authentication-log/pull/30
-   Improve translation strings - https://github.com/rappasoft/laravel-authentication-log/pull/31

### 1.2.1 - 2021-12-02

### Added

-   Added latestAuthentication relationship - https://github.com/rappasoft/laravel-authentication-log/pull/24

### Changed

-   Fixed issue with PHP 7.4 - https://github.com/rappasoft/laravel-authentication-log/pull/22

### 1.2.0 - 2021-11-21

### Added

-   Fire a successful login after a failed login on an unknown (new) device. - https://github.com/rappasoft/laravel-authentication-log/pull/15
-   Make the events the package is listening for configurable in the config file
-   Added French translation and missing location translations - https://github.com/rappasoft/laravel-authentication-log/pull/18
-   PHP 7.4 Support

### 1.1.1 - 2021-10-20

### Changed

-   Logout listener bug fix - https://github.com/rappasoft/laravel-authentication-log/pull/10

### 1.1.0 - 2021-10-11

### Added

-   Known issues section to readme
-   Ability to set DB connection type - https://github.com/rappasoft/laravel-authentication-log/pull/4

## 1.0.0 - 2021-09-30

-   Initial Release
