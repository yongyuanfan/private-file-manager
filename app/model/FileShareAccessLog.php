<?php

namespace app\model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use support\Model;

class FileShareAccessLog extends Model
{
    protected $table = 'file_share_access_logs';

    public $timestamps = false;

    protected $fillable = [
        'file_share_id',
        'action',
        'detail',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function fileShare(): BelongsTo
    {
        return $this->belongsTo(FileShare::class, 'file_share_id');
    }
}
