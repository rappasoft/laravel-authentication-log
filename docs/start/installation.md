---
title: Installation
weight: 1
---

## Requirements

- PHP 8.2 or higher
- Laravel 11.x, 12.x, or 13.x

Use Laravel 12.69+ or 13.30+ for new installations. Laravel 11 compatibility is retained, but it has reached the end of security support and its framework has unpatched [email validation](https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq), [signed URL](https://github.com/advisories/GHSA-crmm-hgp2-wgrp), and [debug page](https://github.com/advisories/GHSA-jh5r-qr3c-85q8) advisories. Composer security checks may reject Laravel 11 installations; upgrading the framework is the remedy.

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
