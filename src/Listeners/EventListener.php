<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Http\Request;
use Rappasoft\LaravelAuthenticationLog\Support\Utils;

abstract class EventListener
{
    public function __construct(public Request $request)
    {
    }

    protected function isLoggable($event): bool
    {
        return Utils::hasAuthenticationLoggableContract($event);
    }

    protected function isListenerForEvent($event, string $config, string $class): bool
    {
        $listener = config("authentication-log.events.$config", $class);

        return $event instanceof $listener;
    }
}
