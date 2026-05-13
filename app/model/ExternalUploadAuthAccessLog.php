<?php

namespace app\model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use support\Model;

class ExternalUploadAuthAccessLog extends Model
{
    protected $table = 'external_upload_auth_access_logs';

    public $timestamps = false;

    protected $fillable = [
        'external_upload_auth_id',
        'user_upload_id',
        'action',
        'detail',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(UserExternalUploadAuth::class, 'external_upload_auth_id');
    }

    public function upload(): BelongsTo
    {
        return $this->belongsTo(UserUpload::class, 'user_upload_id');
    }
}
