<?php

namespace Rappasoft\LaravelAuthenticationLog\Traits;

use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

trait AuthenticationLoggable
{
    public function authentications()
    {
        return $this->morphMany(AuthenticationLog::class, 'authenticatable')->latest('login_at');
    }

    public function latestAuthentication()
    {
        return $this->morphOne(AuthenticationLog::class, 'authenticatable')->latestOfMany('login_at');
    }

    public function notifyAuthenticationLogVia(): array
    {
        return ['mail'];
    }

    public function lastLoginAt()
    {
        return optional($this->authentications()->first())->login_at;
    }

    public function lastSuccessfulLoginAt()
    {
        return optional($this->authentications()->whereLoginSuccessful(true)->first())->login_at;
    }

    public function lastLoginIp()
    {
        return optional($this->authentications()->first())->ip_address;
    }

    public function lastSuccessfulLoginIp()
    {
        return optional($this->authentications()->whereLoginSuccessful(true)->first())->ip_address;
    }

    public function previousLoginAt()
    {
        return optional($this->authentications()->skip(1)->first())->login_at;
    }

    public function previousLoginIp()
    {
        return optional($this->authentications()->skip(1)->first())->ip_address;
    }
}
