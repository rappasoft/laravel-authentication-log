---
title: Upgrade Guide
weight: 3
---

## Upgrading from v5.x (or earlier) to v6.x

Version 6.x introduces new features that require additional database columns. This guide will help you upgrade your existing installation safely.

## What's New in v6.x

- 🔐 **Device Fingerprinting** - Reliable device identification using SHA-256 hashing
- 🚨 **Suspicious Activity Detection** - Automatically detects multiple failed logins, rapid location changes, and unusual login times
- 📊 **Session Management** - View active sessions, revoke specific sessions, or logout all other devices
- 🛡️ **Device Trust Management** - Mark devices as trusted, manage device names, and require trusted devices for sensitive actions
- ⚡ **Rate Limiting** - Prevents notification spam with configurable rate limits
- 🔔 **Webhook Support** - Send webhooks to external services for authentication events
- 📤 **Export Functionality** - Export authentication logs to CSV or JSON format
- 🎯 **Enhanced Query Scopes** - Powerful query scopes for filtering logs

## Upgrade Steps

### 1. Update the Package

```bash
composer update rappasoft/laravel-authentication-log
```

### 2. Publish the Upgrade Migration

```bash
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-migrations"
```

This will publish both:
- The main migration (if you haven't published it before)
- The upgrade migration (`*_add_new_features_to_authentication_log_table.php`)

### 3. Run the Migrations

```bash
php artisan migrate
```

The upgrade migration will:
- ✅ Check if each new column already exists
- ✅ Only add columns that don't exist
- ✅ Preserve all existing data
- ✅ Set safe default values for new columns

## New Database Columns

The following columns will be added to your `authentication_log` table:

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `device_id` | string (nullable, indexed) | `null` | Unique device fingerprint |
| `device_name` | string (nullable) | `null` | Human-readable device name |
| `is_trusted` | boolean | `false` | Whether the device is trusted |
| `last_activity_at` | timestamp (nullable) | `null` | Last activity timestamp |
| `is_suspicious` | boolean | `false` | Suspicious activity flag |
| `suspicious_reason` | string (nullable) | `null` | Reason for suspicious flag |

## Requirements

**Version 6.x requires Laravel 11.x, 12.x, or 13.x.** (Laravel 13.x support requires version 6.1+ and PHP 8.2+.)

If you're still using Laravel 10.x, please continue using version 5.x of this package. Version 6.x is a major release that drops support for Laravel 10.x to simplify the codebase and take advantage of Laravel 11+ features.

## What Happens to Existing Data?

- ✅ **All existing authentication logs are preserved**
- ✅ **No data is modified or deleted**
- ✅ **Existing logs will have `null` values for new columns** (this is safe and expected)
- ✅ **Only new authentication logs created after the upgrade will populate the new columns**

## Rollback

If you need to rollback the upgrade migration:

```bash
php artisan migrate:rollback --step=1
```

**⚠️ Warning:** This will remove the new columns and delete any data stored in them.

## Troubleshooting

### Migration Fails with "Column Already Exists"

This shouldn't happen as the migration checks for column existence. If it does:

1. Check which columns already exist:
   ```bash
   php artisan tinker
   >>> Schema::hasColumn('authentication_log', 'device_id')
   ```

2. If columns already exist, you can skip the migration or manually remove the checks.

### Existing Logs Don't Have Device IDs

This is **expected behavior**. Only new authentication logs created after the upgrade will have device fingerprints. Existing logs will have `null` values, which is safe.

### Features Not Working After Upgrade

1. **Check Laravel version:**
   ```bash
   php artisan --version
   ```
   New features require Laravel 11.x, 12.x, or 13.x.

2. **Verify migrations ran successfully:**
   ```bash
   php artisan migrate:status
   ```

3. **Clear config cache:**
   ```bash
   php artisan config:clear
   ```

4. **Check that columns exist:**
   ```bash
   php artisan tinker
   >>> Schema::hasColumn('authentication_log', 'device_id')
   >>> Schema::hasColumn('authentication_log', 'is_suspicious')
   ```

### Migration Runs But No Columns Added

1. Check if the table exists:
   ```bash
   php artisan tinker
   >>> Schema::hasTable('authentication_log')
   ```

2. Check the migration file was published correctly:
   ```bash
   ls -la database/migrations/*add_new_features*
   ```

3. Check migration logs:
   ```bash
   php artisan migrate:status
   ```

## Verification

After upgrading, verify everything works:

```php
// In tinker or a test route
$user = User::first();

// Basic functionality (works on all Laravel versions)
$user->authentications()->count();
$user->lastLoginAt();

// New features (Laravel 11+ only)
if (\Rappasoft\LaravelAuthenticationLog\Helpers\LaravelVersion::supportsNewFeatures()) {
    $user->getLoginStats();
    $user->getDevices();
    $user->getActiveSessions();
}
```

## Need Help?

If you encounter any issues during the upgrade:

1. Check the [main documentation](/docs/laravel-authentication-log)
2. Review the [changelog](https://github.com/rappasoft/laravel-authentication-log/blob/main/CHANGELOG.md)
3. Open an issue on [GitHub](https://github.com/rappasoft/laravel-authentication-log/issues)

## Next Steps

After upgrading:

1. Review the [configuration options](/docs/laravel-authentication-log/start/configuration) for new features
2. Check out the [usage examples](/docs/laravel-authentication-log/usage/getting-logs) for new features
3. Configure [suspicious activity detection](/docs/laravel-authentication-log/usage/suspicious-activity)
4. Set up [webhooks](/docs/laravel-authentication-log/usage/webhooks) if needed

