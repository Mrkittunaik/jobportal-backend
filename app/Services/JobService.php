<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Job;
use App\Models\Skill;
use App\Support\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JobService
{
    private const VERSION_KEY = 'jobs:version';
    private const FILTERS = ['search', 'company', 'location', 'city', 'state', 'skill', 'experience',
        'salary', 'type', 'category', 'posted', 'page', 'per_page'];

    /** Any job write calls this; cached search pages with the old version are simply never read again. */
    public static function bumpCache(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
    }

    // ------------------------------------------------------------------ public search
    public function search(array $input): LengthAwarePaginator
    {
        $f = array_intersect_key($input, array_flip(self::FILTERS));
        ksort($f);
        $key = 'jobs:'.Cache::get(self::VERSION_KEY, 1).':'.md5(json_encode($f));
        return Cache::remember($key, 60, fn () => $this->runSearch($f));
    }

    private function runSearch(array $f): LengthAwarePaginator
    {
        $q = Job::query()->published()
            ->whereHas('company', fn ($c) => $c->where('verification_status', 'approved'))
            ->with(['company:id,company_name,logo,city,state', 'category:id,name,slug', 'skills:id,name']);

        if (! empty($f['search'])) {
            $like = '%'.$this->esc($f['search']).'%';
            $q->where(fn ($w) => $w->where('title', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('requirements', 'like', $like)
                ->orWhereHas('company', fn ($c) => $c->where('company_name', 'like', $like))
                ->orWhereHas('skills', fn ($s) => $s->where('name', 'like', $like)));
        }
        if (! empty($f['company'])) {
            is_numeric($f['company'])
                ? $q->where('company_id', (int) $f['company'])
                : $q->whereHas('company', fn ($c) => $c->where('company_name', 'like', '%'.$this->esc($f['company']).'%'));
        }
        if (! empty($f['location'])) {
            $like = '%'.$this->esc($f['location']).'%';
            $q->where(fn ($w) => $w->where('location', 'like', $like)->orWhere('city', 'like', $like)->orWhere('state', 'like', $like));
        }
        if (! empty($f['city'])) $q->where('city', 'like', $this->esc($f['city']).'%');
        if (! empty($f['state'])) $q->where('state', 'like', $this->esc($f['state']).'%');
        if (! empty($f['skill'])) {
            $names = array_filter(array_map('trim', explode(',', $f['skill'])));
            $q->whereHas('skills', fn ($s) => $s->whereIn('name', $names));
        }
        if (isset($f['experience']) && is_numeric($f['experience'])) $q->where('experience_min', '<=', (float) $f['experience']);
        if (isset($f['salary']) && is_numeric($f['salary'])) $q->where('salary_max', '>=', (int) $f['salary']);
        if (! empty($f['type'])) $q->where('employment_type', $f['type']);
        if (! empty($f['category'])) {
            is_numeric($f['category'])
                ? $q->where('category_id', (int) $f['category'])
                : $q->whereHas('category', fn ($c) => $c->where('slug', $f['category']));
        }
        if (! empty($f['posted']) && is_numeric($f['posted'])) $q->where('published_at', '>=', now()->subDays((int) $f['posted']));

        $perPage = min(max((int) ($f['per_page'] ?? 15), 1), 50);
        $page = max((int) ($f['page'] ?? 1), 1);
        return $q->orderByDesc('published_at')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);
    }

    public function findPublic(int $id): Job
    {
        return Job::published()
            ->whereHas('company', fn ($c) => $c->where('verification_status', 'approved'))
            ->with(['company:id,company_name,logo,description,website,industry,city,state,company_size', 'category:id,name,slug', 'skills:id,name'])
            ->findOrFail($id);
    }

    // ------------------------------------------------------------------ company side
    public function listForCompany(Company $company, array $f = [])
    {
        return $company->jobs()->withCount('applications')->with(['skills:id,name', 'category:id,name'])
            ->when(! empty($f['status']), fn ($q) => $q->where('status', $f['status']))
            ->when(! empty($f['search']), fn ($q) => $q->where('title', 'like', '%'.$this->esc($f['search']).'%'))
            ->latest('id')->paginate(min(max((int) ($f['per_page'] ?? 15), 1), 50));
    }

    public function findForCompany(Company $company, int $id): Job
    {
        return $company->jobs()->withCount('applications')->with(['skills:id,name', 'category:id,name'])->findOrFail($id);
    }

    public function create(Company $company, array $d): Job
    {
        $d['status'] = $d['status'] ?? 'draft';
        $this->guardPublish($company, $d['status']);
        if ($d['status'] === 'published') $d['published_at'] = now();
        $skills = $d['skills'] ?? null;
        unset($d['skills']);

        $job = DB::transaction(function () use ($company, $d, $skills) {
            $job = $company->jobs()->create($d);
            if ($skills !== null) $this->syncSkills($job, $skills);
            return $job;
        });
        self::bumpCache();
        return $job->load(['skills:id,name', 'category:id,name']);
    }

    public function update(Job $job, array $d): Job
    {
        if (isset($d['status'])) {
            $this->guardPublish($job->company, $d['status']);
            if ($d['status'] === 'published' && ! $job->published_at) $d['published_at'] = now();
        }
        $skills = $d['skills'] ?? null;
        unset($d['skills']);

        DB::transaction(function () use ($job, $d, $skills) {
            $job->update($d);
            if ($skills !== null) $this->syncSkills($job, $skills);
        });
        self::bumpCache();
        return $job->fresh(['skills:id,name', 'category:id,name']);
    }

    public function setStatus(Job $job, string $status): Job
    {
        return $this->update($job, ['status' => $status]);
    }

    public function delete(Job $job): void
    {
        $job->delete();
        self::bumpCache();
    }

    private function syncSkills(Job $job, array $names): void
    {
        $ids = collect($names)->map(fn ($n) => trim($n))->filter()->unique(fn ($n) => mb_strtolower($n))
            ->map(fn ($n) => Skill::firstOrCreate(['name' => $n])->id)->all();
        $job->skills()->sync($ids);
    }

    private function guardPublish(Company $company, string $status): void
    {
        if ($status === 'published' && ! $company->isApproved()) {
            throw new HttpResponseException(ApiResponse::error('Your company is awaiting admin approval. You can save jobs as drafts until then.', 403));
        }
    }

    private function esc(string $s): string
    {
        return addcslashes($s, '%_\\');
    }
}
