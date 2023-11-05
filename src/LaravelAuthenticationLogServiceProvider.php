<?php

namespace Rappasoft\LaravelAuthenticationLog;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Contracts\Events\Dispatcher;
use Rappasoft\LaravelAuthenticationLog\Commands\PurgeAuthenticationLogCommand;
use Rappasoft\LaravelAuthenticationLog\Listeners\FailedLoginListener;
use Rappasoft\LaravelAuthenticationLog\Listeners\LoginListener;
use Rappasoft\LaravelAuthenticationLog\Listeners\LogoutListener;
use Rappasoft\LaravelAuthenticationLog\Listeners\OtherDeviceLogoutListener;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelAuthenticationLogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-authentication-log')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasMigration('create_authentication_log_table')
            ->hasCommand(PurgeAuthenticationLogCommand::class);

        $events = $this->app->make(Dispatcher::class);
        $events->listen(config('authentication-log.events.login', Login::class), config('authentication-log.listeners.login', LoginListener::class));
        $events->listen(config('authentication-log.events.failed', Failed::class), config('authentication-log.listeners.failed', FailedLoginListener::class));
        $events->listen(config('authentication-log.events.logout', Logout::class), config('authentication-log.listeners.logout', LogoutListener::class));
        $events->listen(config('authentication-log.events.other-device-logout', OtherDeviceLogout::class), config('authentication-log.listeners.other-device-logout', OtherDeviceLogoutListener::class));
    }
}
