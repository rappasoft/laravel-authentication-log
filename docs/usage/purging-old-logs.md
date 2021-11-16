---
title: Purging Old Logs
weight: 4
---

You may clear the old authentication log records using the `authentication-log:purge` Artisan command:

```
php artisan authentication-log:purge
```

Records that are older than the number of days specified in the `purge` option in your `config/authentication-log.php` will be deleted.

```php
'purge' => 365,
```

You can also schedule the command at an interval:

```php
$schedule->command('authentication-log:purge')->monthly();
```
