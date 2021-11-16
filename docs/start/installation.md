---
title: Installation
weight: 1
---

You can install the package via composer:

```bash
composer require rappasoft/laravel-authentication-log
```

If you want the location features you must also install `torann/geoip`:

```bash
composer require torann/geoip
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-migrations"
php artisan migrate
```

You can publish the view/email files with:
```bash
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-views"
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-config"
```

This is the contents of the published config file:

```php
return [
    // The database table name
    // You can change this if the database keys get too long for your driver
    'table_name' => 'authentication_log',

    // The database connection where the authentication_log table resides. Leave empty to use the default
    'db_connection' => null,

    'notifications' => [
        'new-device' => [
            // Send the NewDevice notification
            'enabled' => env('NEW_DEVICE_NOTIFICATION', true),

            // Use torann/geoip to attempt to get a location
            'location' => true,

            // The Notification class to send
            'template' => \Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice::class,
        ],
        'failed-login' => [
            // Send the FailedLogin notification
            'enabled' => env('FAILED_LOGIN_NOTIFICATION', false),

            // Use torann/geoip to attempt to get a location
            'location' => true,

            // The Notification class to send
            'template' => \Rappasoft\LaravelAuthenticationLog\Notifications\FailedLogin::class,
        ],
    ],

    // When the clean-up command is run, delete old logs greater than `purge` days
    // Don't schedule the clean-up command if you want to keep logs forever.
    'purge' => 365,
];
```

If you installed `torann/geoip` you should also publish that config file to set your defaults:

```
php artisan vendor:publish --provider="Torann\GeoIP\GeoIPServiceProvider" --tag=config
```
