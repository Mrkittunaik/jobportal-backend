<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = ['name'];

    public function users(): BelongsToMany { return $this->belongsToMany(User::class, 'user_skills'); }
    public function jobs(): BelongsToMany { return $this->belongsToMany(Job::class, 'job_skills'); }
}
