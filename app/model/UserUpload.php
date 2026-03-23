<?php

namespace app\model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use support\Model;

class UserUpload extends Model
{
    protected $table = 'user_uploads';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'storage_path',
        'original_name',
        'extension',
        'file_size',
        'mime_type',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
