<?php

namespace Rappasoft\LaravelAuthenticationLog\Support;

use Rappasoft\LaravelAuthenticationLog\Contracts\AuthenticationLoggableContract;

class Utils
{
    /**
     * Check if the event has a user that implements the AuthenticationLoggableContract
     */
    public static function hasAuthenticationLoggableContract($event): bool
    {
        return isset($event->user) && $event->user instanceof AuthenticationLoggableContract;
    }
}
