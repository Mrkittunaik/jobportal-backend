<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CandidateService
{
    private const RELATIONS = ['educations', 'experiences'];

    public function getProfile(User $user): User
    {
        $user->candidateProfile()->firstOrCreate([]);
        return $user->load(['candidateProfile', 'skills:id,name', 'resumes.file', 'certificates.file']);
    }

    public function updateProfile(User $user, array $d): User
    {
        DB::transaction(function () use ($user, $d) {
            $user->fill(array_intersect_key($d, array_flip(['name', 'phone'])))->save();
            $profile = $user->candidateProfile()->firstOrCreate([]);
            $profile->update(array_diff_key($d, array_flip(['name', 'phone'])));
        });
        return $this->getProfile($user->fresh());
    }

    // ---- Education / Experience: always scoped to the owner, so other users' rows are 404 ----
    public function listItems(User $u, string $rel)
    {
        $this->guard($rel);
        return $u->{$rel}()->latest('id')->get();
    }

    public function createItem(User $u, string $rel, array $d): Model
    {
        $this->guard($rel);
        return $u->{$rel}()->create($this->normalise($rel, $d));
    }

    public function updateItem(User $u, string $rel, int $id, array $d): Model
    {
        $this->guard($rel);
        $m = $u->{$rel}()->findOrFail($id);
        $m->update($this->normalise($rel, $d));
        return $m;
    }

    public function deleteItem(User $u, string $rel, int $id): void
    {
        $this->guard($rel);
        $u->{$rel}()->findOrFail($id)->delete();
    }

    public function skills(User $u)
    {
        return $u->skills()->orderBy('name')->get(['skills.id', 'skills.name']);
    }

    public function syncSkills(User $u, array $names)
    {
        $ids = collect($names)->map(fn ($n) => trim($n))->filter()->unique(fn ($n) => mb_strtolower($n))
            ->map(fn ($n) => Skill::firstOrCreate(['name' => $n])->id)->all();
        $u->skills()->sync($ids);
        return $this->skills($u);
    }

    private function normalise(string $rel, array $d): array
    {
        if ($rel === 'experiences' && ! empty($d['is_current'])) $d['end_date'] = null;
        return $d;
    }

    private function guard(string $rel): void
    {
        abort_unless(in_array($rel, self::RELATIONS, true), 500);
    }
}
