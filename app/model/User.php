<?php

namespace app\model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use support\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'email',
        'password_hash',
        'display_name',
        'plan_id',
        'plan_expires_at',
        'email_verified_at',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $casts = [
        'plan_expires_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'plan_id');
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(UserUpload::class, 'user_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class, 'user_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
