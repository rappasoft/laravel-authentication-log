<?php

namespace Rappasoft\LaravelAuthenticationLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use WhichBrowser\Parser;

/**
 * @property int $id
 * @property string $ip_address
 * @property string $user_agent
 * @property string $browser
 * @property string $browser_os
 * @property Carbon $login_at
 * @property bool $login_successful
 * @property Carbon $logout_at
 * @property bool $cleared_by_user
 * @property array $location
 */
class AuthenticationLog extends Model
{
    public $timestamps = false;

    protected $table = 'authentication_log';

    protected $fillable = [
        'ip_address',
        'user_agent',
        'browser',
        'browser_os',
        'login_at',
        'login_successful',
        'logout_at',
        'cleared_by_user',
        'location',
    ];

    protected $casts = [
        'cleared_by_user' => 'boolean',
        'location' => 'array',
        'login_successful' => 'boolean',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        if (! isset($this->connection)) {
            $this->setConnection(config('authentication-log.db_connection'));
        }

        parent::__construct($attributes);
    }

    protected static function booted(): void
    {
        static::creating(static function ($model) {
            $parser = new Parser($model->user_agent);

            $model->browser = $parser->browser->name;
            $model->browser_os = $parser->os->name;
        });
    }

    public function getTable()
    {
        return config('authentication-log.table_name', parent::getTable());
    }

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }
}
