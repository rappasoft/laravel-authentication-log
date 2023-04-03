<?php

namespace Rappasoft\LaravelAuthenticationLog\Contracts;

interface AuthenticationLoggableContract
{
    public function authentications();

    public function latestAuthentication();

    public function notifyAuthenticationLogVia(): array;

    public function lastLoginAt();

    public function lastSuccessfulLoginAt();

    public function lastLoginIp();

    public function lastSuccessfulLoginIp();

    public function previousLoginAt();

    public function previousLoginIp();
}
