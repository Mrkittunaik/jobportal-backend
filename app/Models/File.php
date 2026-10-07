<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    public const CATEGORIES = ['profile_photo', 'resume', 'certificate', 'company_logo', 'job_image', 'video', 'other'];

    protected $fillable = [
        'user_id', 'file_name', 'original_name', 'file_type', 'file_size', 'storage_path', 'url', 'category',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
