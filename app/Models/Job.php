<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    public const STATUSES = ['draft', 'published', 'closed', 'expired'];
    public const TYPES = ['full-time', 'part-time', 'contract', 'internship', 'remote'];

    protected $table = 'jobs';
    protected $fillable = [
        'company_id', 'category_id', 'title', 'description', 'requirements', 'location', 'city', 'state',
        'employment_type', 'experience_min', 'experience_max', 'salary_min', 'salary_max',
        'vacancy_count', 'application_deadline', 'status', 'published_at',
    ];
    protected $casts = ['application_deadline' => 'date:Y-m-d', 'published_at' => 'datetime'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skills')->using(JobSkill::class)->withTimestamps();
    }
    public function applications(): HasMany { return $this->hasMany(Application::class); }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')
            ->where(fn ($w) => $w->whereNull('application_deadline')->orWhere('application_deadline', '>=', now()->toDateString()));
    }
}
