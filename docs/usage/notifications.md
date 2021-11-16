---
title: Notifications
weight: 2
---

Notifications may be sent on the `mail`, `nexmo`, and `slack` channels but by **default notify via email**.

You may define a `notifyAuthenticationLogVia` method  on your authenticatable models to determine which channels the notification should be delivered on:

```php
public function notifyAuthenticationLogVia()
{
    return ['nexmo', 'mail', 'slack'];
}
```

You must install the [Slack](https://laravel.com/docs/8.x/notifications#routing-slack-notifications) and [Nexmo](https://laravel.com/docs/8.x/notifications#routing-sms-notifications) drivers to use those routes and follow their documentation on setting it up for your specific authenticatable models.

## New Device Notifications

Enabled by default, they use the `\Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice` class which can be overridden in the config file.

## Failed Login Notifications

Disabled by default, they use the `\Rappasoft\LaravelAuthenticationLog\Notifications\FailedLogin` class which can be overridden in the config file.

## Location

If the `torann/geoip` package is installed, it will attempt to include location information to the notifications by default.

You can turn this off within the configuration for each template.

**Note:** By default when working locally, no location will be recorded because it will send back the `default address` from the `geoip` config file. You can override this behavior in the email templates.
