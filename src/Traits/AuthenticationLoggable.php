<?php

namespace Rappasoft\LaravelAuthenticationLog\Traits;

trait AuthenticationLoggable
{
    public function authentications()
    {
        return $this->morphMany(config('authentication-log.model'), 'authenticatable')->latest('login_at');
    }

    public function latestAuthentication()
    {
        return $this->morphOne(config('authentication-log.model'), 'authenticatable')->latestOfMany('login_at');
    }

    public function notifyAuthenticationLogVia(): array
    {
        return ['mail'];
    }

    public function lastLoginAt()
    {
        return $this->authentications()->first()?->login_at;
    }

    public function lastSuccessfulLoginAt()
    {
        return $this->authentications()->whereLoginSuccessful(true)->first()?->login_at;
    }

    public function lastLoginIp()
    {
        return $this->authentications()->first()?->ip_address;
    }

    public function lastSuccessfulLoginIp()
    {
        return $this->authentications()->whereLoginSuccessful(true)->first()?->ip_address;
    }

    public function previousLoginAt()
    {
        return $this->authentications()->skip(1)->first()?->login_at;
    }

    public function previousLoginIp()
    {
        return $this->authentications()->skip(1)->first()?->ip_address;
    }
}
