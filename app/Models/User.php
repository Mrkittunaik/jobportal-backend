<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\RoutesNotifications;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    // RoutesNotifications gives notify() (needed for password reset mail) without
    // Laravel's own `notifications` relation, because we use a custom notifications table.
    use HasApiTokens, HasFactory, RoutesNotifications;

    public const ROLE_CANDIDATE = 'candidate';
    public const ROLE_COMPANY = 'company';
    public const ROLE_ADMIN = 'admin';

    protected $fillable = ['name', 'email', 'phone', 'password', 'role', 'status'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function isAdmin(): bool { return $this->role === self::ROLE_ADMIN; }
    public function isCompany(): bool { return $this->role === self::ROLE_COMPANY; }
    public function isCandidate(): bool { return $this->role === self::ROLE_CANDIDATE; }
    public function isActive(): bool { return $this->status === 'active'; }

    public function candidateProfile(): HasOne { return $this->hasOne(CandidateProfile::class); }
    public function company(): HasOne { return $this->hasOne(Company::class); }
    public function educations(): HasMany { return $this->hasMany(Education::class); }
    public function experiences(): HasMany { return $this->hasMany(Experience::class); }
    public function skills(): BelongsToMany { return $this->belongsToMany(Skill::class, 'user_skills')->withTimestamps(); }
    public function applications(): HasMany { return $this->hasMany(Application::class, 'candidate_id'); }
    public function files(): HasMany { return $this->hasMany(File::class); }
    public function resumes(): HasMany { return $this->hasMany(Resume::class); }
    public function certificates(): HasMany { return $this->hasMany(Certificate::class); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
}
