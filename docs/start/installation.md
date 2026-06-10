---
title: Installation
weight: 1
---

## Requirements

- PHP 8.2 or higher
- Laravel 11.x, 12.x, or 13.x

**Note:** For Laravel 10.x support, please use version 3.x of this package.

## Installation

You can install the package via composer:

```bash
composer require rappasoft/laravel-authentication-log
```

## Optional Dependencies

### Location Features

If you want location tracking features, you must also install `torann/geoip`:

```bash
composer require torann/geoip
```

### SMS Notifications

For SMS notifications via Vonage (formerly Nexmo), install the Vonage package:

```bash
composer require laravel/vonage-notification-channel
```

### Slack Notifications

For Slack notifications, ensure you have the Slack notification channel configured in your Laravel application.

## Next Steps

After installation, you should:

1. [Configure the package](/docs/laravel-authentication-log/start/configuration)
2. Add the `AuthenticationLoggable` trait to your User model
3. Publish and run migrations
4. (Optional) Configure notifications and webhooks
