<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resume extends Model
{
    protected $fillable = ['user_id', 'file_id', 'title', 'is_primary'];
    protected $casts = ['is_primary' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function file(): BelongsTo { return $this->belongsTo(File::class); }
}
