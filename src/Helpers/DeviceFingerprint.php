<?php

namespace Rappasoft\LaravelAuthenticationLog\Helpers;

use Illuminate\Http\Request;

class DeviceFingerprint
{
    /**
     * Generate a device fingerprint that ignores browser version changes.
     * This prevents false "new device" notifications when browsers update.
     */
    public static function generate(Request $request): string
    {
        $components = [
            self::normalizeUserAgent($request->userAgent()),
            $request->ip(),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Normalize user agent by removing version numbers.
     * This ensures browser updates don't trigger false "new device" notifications.
     */
    protected static function normalizeUserAgent(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return '';
        }

        // Remove version numbers (e.g., "Chrome/120.0.0.0" becomes "Chrome")
        // Pattern matches: /version, Version/version, v.version, etc.
        $normalized = preg_replace('/\/(\d+\.\d+\.\d+\.\d+|\d+\.\d+\.\d+|\d+\.\d+|\d+)/', '', $userAgent);

        // Remove "Version X.X" patterns (common in Safari)
        $normalized = preg_replace('/Version\/[\d.]+/i', '', $normalized);

        // Remove "vX.X.X" patterns
        $normalized = preg_replace('/\bv[\d.]+\b/i', '', $normalized);

        // Clean up multiple spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        // Remove trailing/leading spaces and separators
        $normalized = trim($normalized, ' /');

        return $normalized;
    }

    /**
     * Browser patterns, ordered from most to least specific.
     * Most browsers also advertise the tokens of the engine they are based on
     * (e.g. Edge contains "Chrome" and "Safari", Chrome on iOS only contains "CriOS" and "Safari"),
     * so the order matters.
     */
    protected const BROWSERS = [
        'Edge' => '/Edge?\/|EdgA\/|EdgiOS\//i',
        'Opera' => '/OPR\/|OPiOS\/|Opera/i',
        'Firefox' => '/Firefox\/|FxiOS\//i',
        'Chrome' => '/Chrome\/|CriOS\//i',
        'Safari' => '/Safari/i',
        'MSIE' => '/MSIE/i',
        'Trident' => '/Trident/i',
    ];

    /**
     * OS patterns, ordered from most to least specific.
     * iOS user agents contain "like Mac OS X" and Android user agents contain "Linux",
     * so they must be checked first.
     */
    protected const OPERATING_SYSTEMS = [
        'iPad' => '/iPad/i',
        'iPhone' => '/iPhone/i',
        'iOS' => '/iOS/i',
        'Android' => '/Android/i',
        'Windows' => '/Windows/i',
        'Mac' => '/Mac/i',
        'Linux' => '/Linux/i',
    ];

    public static function generateDeviceName(Request $request): string
    {
        $userAgent = (string) $request->userAgent();

        $browser = self::firstMatch(self::BROWSERS, $userAgent) ?? 'Unknown Browser';
        $os = self::firstMatch(self::OPERATING_SYSTEMS, $userAgent) ?? 'Unknown OS';

        return "{$browser} on {$os}";
    }

    /**
     * Return the name of the first pattern matching the user agent.
     *
     * @param  array<string, string>  $patterns
     */
    protected static function firstMatch(array $patterns, string $userAgent): ?string
    {
        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return null;
    }
}
