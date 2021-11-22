# Changelog

All notable changes to `Laravel Authentication Log` will be documented in this file.

### 1.3.0 - 2021-11-XX

### Added

- Added french translation and missing location translations - https://github.com/rappasoft/laravel-authentication-log/pull/18
- Fire a successful login after a failed login on an unknown (new) device. - https://github.com/rappasoft/laravel-authentication-log/pull/15

### 1.2.0 - 2021-11-01

### Added

- Send a new device notification after a failed login attempt (in the event that it was a security issue the user now knows that someone gained entry) - https://github.com/rappasoft/laravel-authentication-log/pull/15

### 1.1.1 - 2021-10-20

### Changed

- Logout listener bug fix - https://github.com/rappasoft/laravel-authentication-log/pull/10

### 1.1.0 - 2021-10-11

### Added

- Known issues section to readme
- Ability to set DB connection type - https://github.com/rappasoft/laravel-authentication-log/pull/4

## 1.0.0 - 2021-09-30

- Initial Release
