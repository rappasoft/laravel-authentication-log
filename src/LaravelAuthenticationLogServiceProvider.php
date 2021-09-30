<?php

namespace Rappasoft\LaravelAuthenticationLog;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Rappasoft\LaravelAuthenticationLog\Listeners\FailedLoginListener;
use Rappasoft\LaravelAuthenticationLog\Listeners\LoginListener;
use Rappasoft\LaravelAuthenticationLog\Listeners\LogoutListener;
use Rappasoft\LaravelAuthenticationLog\Listeners\OtherDeviceLogoutListener;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Contracts\Events\Dispatcher;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Rappasoft\LaravelAuthenticationLog\Commands\PurgeAuthenticationLogCommand;

class LaravelAuthenticationLogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-authentication-log')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_authentication_log_table')
            ->hasCommand(PurgeAuthenticationLogCommand::class);

        $events = $this->app->make(Dispatcher::class);
        $events->listen(Login::class, LoginListener::class);
        $events->listen(Failed::class, FailedLoginListener::class);
        $events->listen(Logout::class, LogoutListener::class);
        $events->listen(OtherDeviceLogout::class, OtherDeviceLogoutListener::class);
    }
}
