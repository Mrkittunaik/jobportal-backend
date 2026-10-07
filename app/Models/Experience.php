<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    protected $table = 'experiences';
    protected $fillable = ['user_id', 'company_name', 'job_title', 'description', 'start_date', 'end_date', 'is_current'];
    protected $casts = ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d', 'is_current' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
