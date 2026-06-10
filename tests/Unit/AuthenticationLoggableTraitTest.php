<?php

use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('can get authentications relationship', function () {
    $user = TestUser::factory()->create();

    expect($user->authentications())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class);
});

it('can get latest authentication relationship', function () {
    $user = TestUser::factory()->create();

    expect($user->latestAuthentication())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphOne::class);
});

it('returns default notification channels', function () {
    $user = TestUser::factory()->create();

    expect($user->notifyAuthenticationLogVia())->toBe(['mail']);
});

it('can get last login at', function () {
    $user = TestUser::factory()->create();

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDay(),
    ]);

    expect($user->lastLoginAt())->not->toBeNull();
    expect($user->lastLoginAt())->toBeInstanceOf(\Carbon\CarbonInterface::class);
});

it('returns null when no authentications exist', function () {
    $user = TestUser::factory()->create();

    expect($user->lastLoginAt())->toBeNull();
    expect($user->lastSuccessfulLoginAt())->toBeNull();
    expect($user->lastLoginIp())->toBeNull();
    expect($user->lastSuccessfulLoginIp())->toBeNull();
    expect($user->previousLoginAt())->toBeNull();
    expect($user->previousLoginIp())->toBeNull();
});

it('can get last successful login ip', function () {
    $user = TestUser::factory()->create();

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDay(),
        'login_successful' => false,
        'ip_address' => '192.168.1.1',
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subHours(2),
        'login_successful' => true,
        'ip_address' => '192.168.1.2',
    ]);

    expect($user->lastSuccessfulLoginIp())->toBe('192.168.1.2');
});
