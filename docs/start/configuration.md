---
title: Configuration
weight: 2
---

## Publishing Assets

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

    // The events the package listens for to log (as of v1.3)
    'events' => [
        'login' => \Illuminate\Auth\Events\Login::class,
        'failed' => \Illuminate\Auth\Events\Failed::class,
        'logout' => \Illuminate\Auth\Events\Logout::class,
        'logout-other-devices' => \Illuminate\Auth\Events\OtherDeviceLogout::class,
    ],

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

## Setting up your model

The models must implement the `AuthenticationLoggableContract`.

You must add the `AuthenticationLoggable` and `Notifiable` traits to the models you want to track.

```php
use Illuminate\Notifications\Notifiable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Rappasoft\LaravelAuthenticationLog\Contracts\AuthenticationLoggableContract;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements AuthenticationLoggableContract
{
    use Notifiable, AuthenticationLoggable;
}
```

The package will listen for Laravel's Login, Logout, Failed, and OtherDeviceLogout events.

## Overriding default Laravel events

If you would like to listen to your own events you may override them in the package config (as of v1.2).

### Example event override

You may notice that Laravel [fires a Login event when the session renews](https://github.com/laravel/framework/blob/master/src/Illuminate/Auth/SessionGuard.php#L149) if the user clicked 'remember me' when logging in. This will produce empty login rows each time which is not what we want. The way around this is to fire your own `Login` event instead of listening for Laravels.

You can create a Login event that takes the user:

```php
<?php

namespace App\Domains\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Login
{
    use SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
}
```

Then override it in the package config:

```php
// The events the package listens for to log
'events' => [
    'login' => \App\Domains\Auth\Events\Login::class,
    ...
],
```

Then call it where you login your user:

```php
event(new Login($user));
```

Now the package will only register actual login events, and not session re-authentications.

### Overriding in Fortify

If you are working with Fortify and would like to register your own Login event, you can append a class to the authentication stack:

In FortifyServiceProvider:

```php
Fortify::authenticateThrough(function () {
    return array_filter([
        ...
        FireLoginEvent::class,
    ]);
});
```

`FireLoginEvent` is just a class that fires the event:

```php
<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\Events\Login;

class FireLoginEvent
{
    public function handle($request, $next)
    {
        if ($request->user()) {
            event(new Login($request->user()));
        }

        return $next($request);
    }
}
```
