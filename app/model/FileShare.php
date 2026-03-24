<?php

namespace app\model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use support\Model;

class FileShare extends Model
{
    protected $table = 'file_shares';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_upload_id',
        'token',
        'password_hash',
        'max_views',
        'view_count',
        'expires_at',
        'revoked_at',
        'created_at',
    ];

    protected $casts = [
        'max_views' => 'integer',
        'view_count' => 'integer',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function userUpload(): BelongsTo
    {
        return $this->belongsTo(UserUpload::class, 'user_upload_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(FileShareAccessLog::class, 'file_share_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(Carbon $now): bool
    {
        return $this->expires_at !== null && $this->expires_at->lte($now);
    }

    public function hasPassword(): bool
    {
        return $this->password_hash !== null && $this->password_hash !== '';
    }

    public function viewsExhausted(): bool
    {
        if ($this->max_views === null) {
            return false;
        }

        return (int) $this->view_count >= (int) $this->max_views;
    }
}
