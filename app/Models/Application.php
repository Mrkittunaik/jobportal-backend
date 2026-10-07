<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    public const STATUSES = ['applied', 'under_review', 'shortlisted', 'interview', 'selected', 'rejected', 'withdrawn'];

    protected $fillable = ['job_id', 'candidate_id', 'resume_id', 'cover_letter', 'status', 'applied_at'];
    protected $casts = ['applied_at' => 'datetime'];

    public function job(): BelongsTo { return $this->belongsTo(Job::class); }
    public function candidate(): BelongsTo { return $this->belongsTo(User::class, 'candidate_id'); }
    public function resume(): BelongsTo { return $this->belongsTo(Resume::class); }
    public function statusHistory(): HasMany { return $this->hasMany(ApplicationStatusHistory::class)->oldest(); }
}
