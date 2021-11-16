---
title: Getting Logs
weight: 1
---

Get all authentication logs for the user:
```php
User::find(1)->authentications;
```

Get the user's last login information:
```php
User::find(1)->lastLoginAt();

User::find(1)->lastSuccessfulLoginAt();

User::find(1)->lastLoginIp();

User::find(1)->lastSuccessfulLoginIp();
```

Get the user's previous login time & IP address (ignoring the current login):
```php
auth()->user()->previousLoginAt();

auth()->user()->previousLoginIp();
```
