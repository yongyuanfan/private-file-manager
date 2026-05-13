<?php

namespace app\model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use support\Model;

class UserExternalUploadAuth extends Model
{
    protected $table = 'user_external_upload_auths';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'status',
        'default_subdir',
        'retention_ttl_days',
        'created_at',
        'last_used_at',
        'revoked_at',
    ];

    protected $casts = [
        'retention_ttl_days' => 'integer',
        'created_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->revoked_at === null;
    }

    public function retentionExpiresAt(Carbon $now): ?Carbon
    {
        if ($this->retention_ttl_days === null) {
            return null;
        }

        return $now->copy()->addDays(max(0, (int) $this->retention_ttl_days));
    }
}
