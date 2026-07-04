<?php

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Notifications\SuspiciousActivity;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
    Cache::flush();

    // Reset Carbon time to ensure test isolation (prevents time mocking from leaking between tests)
    \Illuminate\Support\Carbon::setTestNow();
});

it('sends suspicious activity notification when enabled and multiple failed logins detected', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => true,
        'authentication-log.suspicious.failed_login_threshold' => 3,
    ]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10),
    ]);

    // Create 3 failed logins to trigger threshold
    for ($i = 0; $i < 3; $i++) {
        Event::dispatch(new Failed('web', $user, []));
    }

    Notification::assertSentTo($user, SuspiciousActivity::class, function ($notification) {
        return ! empty($notification->suspiciousActivities) &&
               $notification->suspiciousActivities[0]['type'] === 'multiple_failed_logins';
    });
});

it('does not send suspicious activity notification when disabled', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => false,
        'authentication-log.suspicious.failed_login_threshold' => 3,
    ]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10),
    ]);

    // Create 3 failed logins to trigger threshold
    for ($i = 0; $i < 3; $i++) {
        Event::dispatch(new Failed('web', $user, []));
    }

    Notification::assertNothingSent();
});

it('does not send notification when suspicious activity is not detected', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => true,
        'authentication-log.suspicious.failed_login_threshold' => 5,
    ]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10),
    ]);

    // Create only 2 failed logins (below threshold)
    for ($i = 0; $i < 2; $i++) {
        Event::dispatch(new Failed('web', $user, []));
    }

    Notification::assertNothingSent();
});

it('sends notification for rapid location changes during login', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => true,
        'authentication-log.notifications.new-device.enabled' => false, // Disable new device notifications
        'authentication-log.notifications.new-device.location' => false, // Disable geoip to manually set location
    ]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10),
    ]);

    // Set request values and generate device ID
    $sameIp = '192.168.1.1';
    $sameUserAgent = 'Test Browser';
    request()->server->set('REMOTE_ADDR', $sameIp);
    request()->headers->set('User-Agent', $sameUserAgent);
    $deviceId = \Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint::generate(request());

    // Create first login from United States with matching device_id
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(30),
        // Pin last activity outside the restoration window — the factory randomizes
        // it, and a recent value would make the login below a session restoration.
        'last_activity_at' => now()->subMinutes(30),
        'ip_address' => $sameIp,
        'user_agent' => $sameUserAgent,
        'device_id' => $deviceId,
        'location' => [
            'default' => false,
            'country' => 'United States',
            'city' => 'New York',
        ],
    ]);

    // Create second login from United Kingdom (different country) with matching device_id
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(20),
        'last_activity_at' => now()->subMinutes(20),
        'ip_address' => $sameIp,
        'user_agent' => $sameUserAgent,
        'device_id' => $deviceId,
        'location' => [
            'default' => false,
            'country' => 'United Kingdom',
            'city' => 'London',
        ],
    ]);

    // Trigger login event which will detect suspicious activity
    Event::dispatch(new Login('web', $user, false));

    Notification::assertSentTo($user, SuspiciousActivity::class, function ($notification) {
        return ! empty($notification->suspiciousActivities) &&
               $notification->suspiciousActivities[0]['type'] === 'rapid_location_change';
    });
});

it('sends notification for unusual login times when enabled', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => true,
        'authentication-log.notifications.new-device.enabled' => false, // Disable new device notifications
        'authentication-log.suspicious.check_unusual_times' => true,
        'authentication-log.suspicious.usual_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
    ]);

    // Set current time to 3 AM (outside usual hours) FIRST, before creating user/logs
    $testTime = \Illuminate\Support\Carbon::create(2024, 1, 1, 3, 0, 0);
    \Illuminate\Support\Carbon::setTestNow($testTime);

    $user = TestUser::factory()->create([
        'created_at' => $testTime->copy()->subMinutes(10),
    ]);

    // Set request values and generate device ID
    $sameIp = '192.168.1.1';
    $sameUserAgent = 'Test Browser';
    request()->server->set('REMOTE_ADDR', $sameIp);
    request()->headers->set('User-Agent', $sameUserAgent);
    $deviceId = \Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint::generate(request());

    // Create a previous successful login so device is recognized as known (30 minutes before test time)
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => $testTime->copy()->subMinutes(30),
        // Pin last activity outside the restoration window — the factory randomizes
        // it, and a recent value would make the login below a session restoration.
        'last_activity_at' => $testTime->copy()->subMinutes(30),
        'ip_address' => $sameIp,
        'user_agent' => $sameUserAgent,
        'device_id' => $deviceId,
    ]);

    Event::dispatch(new Login('web', $user, false));

    Notification::assertSentTo($user, SuspiciousActivity::class, function ($notification) {
        return ! empty($notification->suspiciousActivities) &&
               $notification->suspiciousActivities[0]['type'] === 'unusual_login_time';
    });

    // Reset time
    \Illuminate\Support\Carbon::setTestNow();
});

it('does not send notification for unusual login times when check is disabled', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => true,
        'authentication-log.notifications.new-device.enabled' => false, // Disable new device notifications
        'authentication-log.suspicious.check_unusual_times' => false,
        'authentication-log.suspicious.usual_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
    ]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10),
    ]);

    // Set current time to 3 AM (outside usual hours) FIRST, before creating logs
    $testTime = \Illuminate\Support\Carbon::create(2024, 1, 1, 3, 0, 0);
    \Illuminate\Support\Carbon::setTestNow($testTime);

    // Set request values and generate device ID
    $sameIp = '192.168.1.1';
    $sameUserAgent = 'Test Browser';
    request()->server->set('REMOTE_ADDR', $sameIp);
    request()->headers->set('User-Agent', $sameUserAgent);
    $deviceId = \Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint::generate(request());

    // Create a previous successful login so device is recognized as known (30 minutes before test time)
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => $testTime->copy()->subMinutes(30),
        // Pin last activity outside the restoration window — the factory randomizes
        // it, and a recent value would make the login below a session restoration.
        'last_activity_at' => $testTime->copy()->subMinutes(30),
        'ip_address' => $sameIp,
        'user_agent' => $sameUserAgent,
        'device_id' => $deviceId,
    ]);

    Event::dispatch(new Login('web', $user, false));

    Notification::assertNothingSent();

    // Reset time
    \Illuminate\Support\Carbon::setTestNow();
});

it('respects rate limiting for suspicious activity notifications', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => true,
        'authentication-log.notifications.suspicious-activity.rate_limit' => 2,
        'authentication-log.notifications.suspicious-activity.rate_limit_decay' => 60,
        'authentication-log.suspicious.failed_login_threshold' => 3,
    ]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10),
    ]);

    // First batch: 3 failed logins (should trigger notification)
    for ($i = 0; $i < 3; $i++) {
        Event::dispatch(new Failed('web', $user, []));
    }

    Notification::assertSentTo($user, SuspiciousActivity::class, 1);

    // Clear notifications
    Notification::fake();

    // Second batch: 3 more failed logins (should trigger notification - within rate limit)
    for ($i = 0; $i < 3; $i++) {
        Event::dispatch(new Failed('web', $user, []));
    }

    Notification::assertSentTo($user, SuspiciousActivity::class, 1);

    // Clear notifications
    Notification::fake();

    // Third batch: 3 more failed logins (should NOT trigger notification - rate limit exceeded)
    for ($i = 0; $i < 3; $i++) {
        Event::dispatch(new Failed('web', $user, []));
    }

    Notification::assertNothingSent();
});

it('sends notification with correct suspicious activity details', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => true,
        'authentication-log.suspicious.failed_login_threshold' => 3,
    ]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10),
    ]);

    // Create 5 failed logins with slight time differences to ensure they're all counted
    // The last one will trigger the notification and should see all 5
    for ($i = 0; $i < 5; $i++) {
        Event::dispatch(new Failed('web', $user, []));
        // Small delay to ensure logs are created before next event
        usleep(1000); // 1ms delay
    }

    Notification::assertSentTo($user, SuspiciousActivity::class, function ($notification) use ($user) {
        expect($notification->authenticationLog)->toBeInstanceOf(AuthenticationLog::class);
        expect($notification->suspiciousActivities)->not->toBeEmpty();
        expect($notification->suspiciousActivities[0]['type'])->toBe('multiple_failed_logins');
        // The count should be at least 3 (threshold) but could be up to 5
        // We'll check that it's at least the threshold
        expect($notification->suspiciousActivities[0]['count'])->toBeGreaterThanOrEqual(3);
        expect($notification->suspiciousActivities[0]['message'])->toContain('failed login attempts');

        return true;
    });
});

it('does not send notification for failed logins outside the time window', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => true,
        'authentication-log.suspicious.failed_login_threshold' => 3,
    ]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10),
    ]);

    // Create 3 failed logins more than 1 hour ago
    AuthenticationLog::factory()->count(3)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subMinutes(61),
    ]);

    // Create 1 more recent failed login (below threshold)
    Event::dispatch(new Failed('web', $user, []));

    Notification::assertNothingSent();
});

it('sends notification only once per suspicious activity detection', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => true,
        'authentication-log.suspicious.failed_login_threshold' => 3,
    ]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10),
    ]);

    // Create 3 failed logins to trigger threshold
    for ($i = 0; $i < 3; $i++) {
        Event::dispatch(new Failed('web', $user, []));
    }

    // Should only send one notification
    Notification::assertSentTo($user, SuspiciousActivity::class, 1);
});

it('does not send notification for rapid location change when locations are same country', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => true,
        'authentication-log.notifications.new-device.enabled' => false, // Disable new device notifications
        'authentication-log.notifications.new-device.location' => false,
    ]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10),
    ]);

    // Use same device fingerprint for all logins
    $sameIp = '192.168.1.1';
    $sameUserAgent = 'Test Browser';

    // Set request values first
    request()->server->set('REMOTE_ADDR', $sameIp);
    request()->headers->set('User-Agent', $sameUserAgent);

    // Generate device ID that matches what DeviceFingerprint would generate
    $deviceId = \Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint::generate(request());

    // Create first login from New York with matching device_id
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(30),
        // Pin last activity outside the restoration window — the factory randomizes
        // it, and a recent value would make the login below a session restoration.
        'last_activity_at' => now()->subMinutes(30),
        'ip_address' => $sameIp,
        'user_agent' => $sameUserAgent,
        'device_id' => $deviceId,
        'location' => [
            'default' => false,
            'country' => 'United States',
            'city' => 'New York',
        ],
    ]);

    // Create second login from Los Angeles (same country) with matching device_id
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(20),
        'last_activity_at' => now()->subMinutes(20),
        'ip_address' => $sameIp,
        'user_agent' => $sameUserAgent,
        'device_id' => $deviceId,
        'location' => [
            'default' => false,
            'country' => 'United States',
            'city' => 'Los Angeles',
        ],
    ]);

    // Trigger login event with same device fingerprint
    Event::dispatch(new Login('web', $user, false));

    Notification::assertNothingSent();
});

it('sends notification for multiple types of suspicious activity', function () {
    Notification::fake();

    config([
        'authentication-log.notifications.suspicious-activity.enabled' => true,
        'authentication-log.suspicious.failed_login_threshold' => 3,
        'authentication-log.suspicious.check_unusual_times' => true,
        'authentication-log.suspicious.usual_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
        'authentication-log.notifications.new-device.location' => false,
    ]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10),
    ]);

    // Create 3 failed logins
    for ($i = 0; $i < 3; $i++) {
        Event::dispatch(new Failed('web', $user, []));
    }

    // Create rapid location change
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(30),
        'location' => [
            'default' => false,
            'country' => 'United States',
            'city' => 'New York',
        ],
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(20),
        'location' => [
            'default' => false,
            'country' => 'United Kingdom',
            'city' => 'London',
        ],
    ]);

    // Set time to unusual hour
    \Illuminate\Support\Carbon::setTestNow(now()->setHour(3)->setMinute(0));

    // Trigger login which should detect multiple suspicious activities
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');
    Event::dispatch(new Login('web', $user, false));

    Notification::assertSentTo($user, SuspiciousActivity::class, function ($notification) {
        $types = collect($notification->suspiciousActivities)->pluck('type')->toArray();

        // Should have multiple types of suspicious activity
        return count($types) > 1;
    });

    // Reset time
    \Illuminate\Support\Carbon::setTestNow();
});
