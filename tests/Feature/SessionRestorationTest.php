<?php

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('prevents session restoration from creating duplicate log entries', function () {
    config(['authentication-log.prevent_session_restoration_logging' => true]);
    config(['authentication-log.session_restoration_window_minutes' => 5]);

    $user = TestUser::factory()->create();

    // Set up device
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');

    // First login - should create a log entry
    Event::dispatch(new Login('web', $user, false));

    $initialCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($initialCount)->toBe(1);

    $firstLog = AuthenticationLog::where('authenticatable_id', $user->id)->first();
    $initialLastActivity = $firstLog->last_activity_at;

    // Simulate session restoration (page refresh) within the window
    // Wait a moment to ensure timestamps are different
    sleep(1);
    Event::dispatch(new Login('web', $user, false));

    // Should still be 1 log entry (not duplicated)
    $afterRestorationCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($afterRestorationCount)->toBe(1);

    // But last_activity_at should be updated
    $firstLog->refresh();
    expect($firstLog->last_activity_at->timestamp)->toBeGreaterThan($initialLastActivity->timestamp);
});

it('creates new log entry if session restoration prevention is disabled', function () {
    config(['authentication-log.prevent_session_restoration_logging' => false]);

    $user = TestUser::factory()->create();

    // Set up device
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');

    // First login
    Event::dispatch(new Login('web', $user, false));

    $initialCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($initialCount)->toBe(1);

    // Simulate session restoration
    Event::dispatch(new Login('web', $user, false));

    // Should create a new log entry when prevention is disabled
    $afterRestorationCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($afterRestorationCount)->toBe(2);
});

it('keeps one log entry for a session that stays active past its original login window', function () {
    config(['authentication-log.prevent_session_restoration_logging' => true]);
    config(['authentication-log.session_restoration_window_minutes' => 5]);

    $user = TestUser::factory()->create();

    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');

    Event::dispatch(new Login('web', $user, false));
    expect(AuthenticationLog::where('authenticatable_id', $user->id)->count())->toBe(1);

    // The session has been active for ~6 minutes: its original login_at is now
    // older than the 5-minute window, but restorations kept last_activity_at fresh.
    // Anchoring the lookup on login_at (the bug) would spawn a second row here.
    $log = AuthenticationLog::where('authenticatable_id', $user->id)->first();
    $log->update([
        'login_at' => now()->subMinutes(6),
        'last_activity_at' => now()->subMinute(),
    ]);

    Event::dispatch(new Login('web', $user, false));

    // Still one entry — the active session is recognized via last_activity_at.
    expect(AuthenticationLog::where('authenticatable_id', $user->id)->count())->toBe(1);

    $log->refresh();
    expect($log->last_activity_at->timestamp)->toBeGreaterThan(now()->subMinute()->timestamp);
});

it('restores recent legacy active sessions with null last activity', function () {
    config(['authentication-log.prevent_session_restoration_logging' => true]);
    config(['authentication-log.session_restoration_window_minutes' => 5]);

    $user = TestUser::factory()->create();

    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');

    $log = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => \Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint::generate(request()),
        'login_at' => now()->subMinutes(2),
        'login_successful' => true,
        'logout_at' => null,
        'last_activity_at' => null,
    ]);

    Event::dispatch(new Login('web', $user, false));

    expect(AuthenticationLog::where('authenticatable_id', $user->id)->count())->toBe(1);
    expect($log->refresh()->last_activity_at)->not->toBeNull();
});

it('creates new log entry if existing session is outside restoration window', function () {
    config(['authentication-log.prevent_session_restoration_logging' => true]);
    config(['authentication-log.session_restoration_window_minutes' => 5]);

    $user = TestUser::factory()->create();

    // Set up device
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');

    // Create an old session whose last activity is outside the window
    $oldLog = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => \Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint::generate(request()),
        'login_at' => now()->subMinutes(10),
        'login_successful' => true,
        'logout_at' => null,
        'last_activity_at' => now()->subMinutes(10),
    ]);

    $initialCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($initialCount)->toBe(1);

    // New login (outside window) - should create new entry
    Event::dispatch(new Login('web', $user, false));

    $afterLoginCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($afterLoginCount)->toBe(2);
});

it('creates new log entry if existing session is logged out', function () {
    config(['authentication-log.prevent_session_restoration_logging' => true]);
    config(['authentication-log.session_restoration_window_minutes' => 5]);

    $user = TestUser::factory()->create();

    // Set up device
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');

    // Create a logged out session
    $loggedOutLog = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => \Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint::generate(request()),
        'login_at' => now()->subMinutes(2),
        'login_successful' => true,
        'logout_at' => now()->subMinute(),
    ]);

    $initialCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($initialCount)->toBe(1);

    // New login (previous session was logged out) - should create new entry
    Event::dispatch(new Login('web', $user, false));

    $afterLoginCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($afterLoginCount)->toBe(2);
});

it('creates new log entry for different device even within window', function () {
    config(['authentication-log.prevent_session_restoration_logging' => true]);
    config(['authentication-log.session_restoration_window_minutes' => 5]);

    $user = TestUser::factory()->create();

    // First login from device 1
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Device 1 Browser');
    Event::dispatch(new Login('web', $user, false));

    $initialCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($initialCount)->toBe(1);

    // Login from different device (different IP/UA = different device_id)
    request()->server->set('REMOTE_ADDR', '192.168.1.100');
    request()->headers->set('User-Agent', 'Device 2 Browser');
    Event::dispatch(new Login('web', $user, false));

    // Should create new entry for different device
    $afterLoginCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($afterLoginCount)->toBe(2);
});

it('respects configurable restoration window', function () {
    config(['authentication-log.prevent_session_restoration_logging' => true]);
    config(['authentication-log.session_restoration_window_minutes' => 1]); // 1 minute window

    $user = TestUser::factory()->create();

    // Set up device
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');

    // First login
    Event::dispatch(new Login('web', $user, false));

    $initialCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($initialCount)->toBe(1);

    // Age the session's last activity outside the 1-minute window
    $oldLog = AuthenticationLog::where('authenticatable_id', $user->id)->first();
    $oldLog->update(['login_at' => now()->subMinutes(2), 'last_activity_at' => now()->subMinutes(2)]);

    // New login (outside 1-minute window) - should create new entry
    Event::dispatch(new Login('web', $user, false));

    $afterLoginCount = AuthenticationLog::where('authenticatable_id', $user->id)->count();
    expect($afterLoginCount)->toBe(2);
});
