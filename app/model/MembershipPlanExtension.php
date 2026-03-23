<?php

namespace app\model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use support\Model;

class MembershipPlanExtension extends Model
{
    protected $table = 'membership_plan_extensions';

    public $timestamps = false;

    protected $fillable = [
        'plan_id',
        'extension',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'plan_id');
    }
}
