# Changelog

All notable changes to `Laravel Authentication Log` will be documented in this file.

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
