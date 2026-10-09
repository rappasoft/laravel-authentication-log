<?php

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint;
use Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('generates consistent device fingerprint', function () {
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');

    $fingerprint1 = DeviceFingerprint::generate(request());
    $fingerprint2 = DeviceFingerprint::generate(request());

    expect($fingerprint1)->toBe($fingerprint2);
});

it('generates different fingerprints for different devices', function () {
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Browser 1');

    $fingerprint1 = DeviceFingerprint::generate(request());

    request()->headers->set('User-Agent', 'Browser 2');

    $fingerprint2 = DeviceFingerprint::generate(request());

    expect($fingerprint1)->not->toBe($fingerprint2);
});

it('generates same fingerprint for browser version updates', function () {
    // Safari 14.1.2
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Safari/605.1.15');

    $fingerprint1 = DeviceFingerprint::generate(request());

    // Safari 15.1 (updated version)
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.1 Safari/605.1.15');

    $fingerprint2 = DeviceFingerprint::generate(request());

    // Should be the same fingerprint despite version change
    expect($fingerprint1)->toBe($fingerprint2);
});

it('generates same fingerprint for Chrome version updates', function () {
    request()->server->set('REMOTE_ADDR', '192.168.1.1');

    // Chrome 120.0.0.0
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    $fingerprint1 = DeviceFingerprint::generate(request());

    // Chrome 121.0.0.0 (updated version)
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');

    $fingerprint2 = DeviceFingerprint::generate(request());

    // Should be the same fingerprint despite version change
    expect($fingerprint1)->toBe($fingerprint2);
});

it('generates different fingerprints for different browsers', function () {
    request()->server->set('REMOTE_ADDR', '192.168.1.1');

    // Chrome
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $fingerprint1 = DeviceFingerprint::generate(request());

    // Firefox
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0');
    $fingerprint2 = DeviceFingerprint::generate(request());

    // Should be different
    expect($fingerprint1)->not->toBe($fingerprint2);
});

it('generates different fingerprints for different IPs', function () {
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    $fingerprint1 = DeviceFingerprint::generate(request());

    request()->server->set('REMOTE_ADDR', '192.168.1.2');
    $fingerprint2 = DeviceFingerprint::generate(request());

    // Should be different
    expect($fingerprint1)->not->toBe($fingerprint2);
});

it('generates different fingerprints for different operating systems', function () {
    request()->server->set('REMOTE_ADDR', '192.168.1.1');

    // Windows
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $fingerprint1 = DeviceFingerprint::generate(request());

    // Mac
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $fingerprint2 = DeviceFingerprint::generate(request());

    // Should be different
    expect($fingerprint1)->not->toBe($fingerprint2);
});

it('does not send new device notification on browser version update', function () {
    Notification::fake();

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(2),
    ]);

    // First login with Safari 14.1.2
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Safari/605.1.15');
    Event::dispatch(new Login('web', $user, false));

    // Clear notifications from first login
    Notification::fake();

    // Second login with Safari 15.1 (updated version, same device)
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.1 Safari/605.1.15');
    Event::dispatch(new Login('web', $user, false));

    // Should NOT send new device notification because fingerprint is the same
    Notification::assertNothingSent();
});

it('sends new device notification when browser actually changes', function () {
    Notification::fake();

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(2),
    ]);

    // First login with Chrome
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    Event::dispatch(new Login('web', $user, false));

    // Clear notifications from first login
    Notification::fake();

    // Second login with Firefox (different browser)
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0');
    Event::dispatch(new Login('web', $user, false));

    // Should send new device notification because fingerprint is different
    Notification::assertSentTo($user, NewDevice::class);
});

it('generates device name from user agent', function () {
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $deviceName = DeviceFingerprint::generateDeviceName(request());

    expect($deviceName)->toContain('Windows');
});

it('detects browser and OS from user agent', function (?string $userAgent, string $expected) {
    request()->headers->set('User-Agent', $userAgent);

    expect(DeviceFingerprint::generateDeviceName(request()))->toBe($expected);
})->with([
    'Chrome on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', 'Chrome on Windows'],
    'Chrome on Mac' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', 'Chrome on Mac'],
    'Chrome on Android' => ['Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', 'Chrome on Android'],
    'Chrome on iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 26_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/153.0.8010.24 Mobile/15E148 Safari/604.1', 'Chrome on iPhone'],
    'Chrome on iPad' => ['Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0.6099.119 Mobile/15E148 Safari/604.1', 'Chrome on iPad'],
    'Safari on Mac' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.1 Safari/605.1.15', 'Safari on Mac'],
    'Safari on iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1', 'Safari on iPhone'],
    'Firefox on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0', 'Firefox on Windows'],
    'Firefox on Linux' => ['Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0', 'Firefox on Linux'],
    'Firefox on iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/121.0 Mobile/15E148 Safari/605.1.15', 'Firefox on iPhone'],
    'Edge on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.2210.91', 'Edge on Windows'],
    'Edge on Android' => ['Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36 EdgA/120.0.2210.84', 'Edge on Android'],
    'Edge on iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 EdgiOS/120.0.2210.126 Mobile/15E148 Safari/605.1.15', 'Edge on iPhone'],
    'Opera on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0', 'Opera on Windows'],
    'Legacy Edge on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/42.0.2311.135 Safari/537.36 Edge/12.246', 'Edge on Windows'],
    'Opera on iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 OPiOS/2.2.0 Mobile/15E148 Safari/605.1.15', 'Opera on iPhone'],
    'Internet Explorer 10' => ['Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)', 'MSIE on Windows'],
    'Internet Explorer 11' => ['Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko', 'Trident on Windows'],
    'Empty' => ['', 'Unknown Browser on Unknown OS'],
    'Missing' => [null, 'Unknown Browser on Unknown OS'],
    'Unknown' => ['Test Browser', 'Unknown Browser on Unknown OS'],
]);

it('stores device fingerprint on login', function () {
    $user = TestUser::factory()->create();

    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');

    Event::dispatch(new Login('web', $user, false));

    $log = $user->authentications()->first();
    expect($log->device_id)->not->toBeNull();
    expect($log->device_name)->not->toBeNull();
});
