<?php

namespace app\model;

use Illuminate\Database\Eloquent\Relations\HasMany;
use support\Model;

class MembershipPlan extends Model
{
    protected $table = 'membership_plans';

    protected $fillable = [
        'code',
        'name',
        'description',
        'max_uploads',
        'quota_period',
        'max_file_size',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'max_uploads' => 'integer',
        'max_file_size' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function allowedExtensions(): HasMany
    {
        return $this->hasMany(MembershipPlanExtension::class, 'plan_id');
    }
}
