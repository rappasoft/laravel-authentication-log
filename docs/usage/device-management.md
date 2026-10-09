---
title: Device Management
weight: 4
---

The package uses device fingerprinting to reliably identify and manage user devices. Each device is assigned a unique fingerprint based on browser characteristics, IP address, and other factors.

## Device Fingerprinting

Device fingerprinting uses SHA-256 hashing to create a unique identifier for each device. This is more reliable than using just IP address and user agent, as it accounts for multiple factors.

### How It Works

When a user logs in, the package automatically:
1. Generates a device fingerprint using browser characteristics
2. Creates a friendly device name (e.g., "Chrome on Windows")
3. Checks if the device has been seen before
4. Stores the device information with the authentication log

Generated names recognize Chrome, Firefox, Edge, Opera, and Safari, including their iOS user agents. Browser-specific tokens take precedence over shared engine tokens, and Android and iOS take precedence over Linux and Mac. A missing or unrecognized user agent produces `Unknown Browser on Unknown OS`.

Device names are display labels. Improved name detection does not change existing `device_id` fingerprints, device trust, or new-device notifications. Existing stored names remain until a new log is created or you call `updateDeviceName()`.

## Getting User Devices

Get all devices that have been used by a user:

```php
$user = User::find(1);
$devices = $user->getDevices();

// Returns a collection with:
// - device_id
// - device_name
// - ip_address
// - user_agent
// - is_trusted
// - login_at (most recent)
```

## Trusting Devices

Mark a device as trusted to allow it access to sensitive actions:

```php
$user = User::find(1);
$deviceId = 'abc123...'; // Device fingerprint

// Trust a device
$user->trustDevice($deviceId);

// Check if device is trusted
if ($user->isDeviceTrusted($deviceId)) {
    // Device is trusted
}
```

## Untrusting Devices

Remove trust from a device:

```php
$user = User::find(1);
$deviceId = 'abc123...';

$user->untrustDevice($deviceId);
```

## Updating Device Names

Allow users to customize device names for easier identification:

```php
$user = User::find(1);
$deviceId = 'abc123...';

$user->updateDeviceName($deviceId, 'My Work Laptop');
```

## Example: Device Management UI

```php
// In your controller
public function devices()
{
    $user = auth()->user();
    $devices = $user->getDevices();
    
    return view('profile.devices', compact('devices'));
}

// In your view
@foreach($devices as $device)
    <div class="device-item">
        <div>
            <strong>{{ $device->device_name }}</strong>
            @if($device->is_trusted)
                <span class="badge badge-success">Trusted</span>
            @endif
        </div>
        <div>
            Last used: {{ $device->login_at->diffForHumans() }}
        </div>
        <div>
            <form action="{{ route('devices.trust', $device->device_id) }}" method="POST">
                @csrf
                @if($device->is_trusted)
                    <button type="submit" name="action" value="untrust">Untrust Device</button>
                @else
                    <button type="submit" name="action" value="trust">Trust Device</button>
                @endif
            </form>
        </div>
    </div>
@endforeach
```

## Getting Current Device ID

To get the current device's fingerprint:

```php
use Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint;

$currentDeviceId = DeviceFingerprint::generate(request());
$deviceName = DeviceFingerprint::generateDeviceName(request());
```

