<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProfile extends Model
{
    protected $fillable = [
        'user_id', 'profile_photo', 'date_of_birth', 'gender', 'location', 'city', 'state',
        'bio', 'current_job_title', 'total_experience',
    ];
    protected $casts = ['date_of_birth' => 'date:Y-m-d', 'total_experience' => 'float'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
