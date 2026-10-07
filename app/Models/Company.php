<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'blocked'];

    protected $fillable = [
        'user_id', 'company_name', 'logo', 'description', 'website', 'email', 'phone',
        'industry', 'location', 'city', 'state', 'company_size', 'verification_status',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function jobs(): HasMany { return $this->hasMany(Job::class); }
    public function isApproved(): bool { return $this->verification_status === 'approved'; }
}
