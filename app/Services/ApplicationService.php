<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    /** Allowed moves. Terminal states (selected, rejected, withdrawn) have none. */
    public const TRANSITIONS = [
        'applied'      => ['under_review', 'shortlisted', 'rejected'],
        'under_review' => ['shortlisted', 'interview', 'rejected'],
        'shortlisted'  => ['interview', 'selected', 'rejected'],
        'interview'    => ['selected', 'rejected'],
    ];

    private const CANDIDATE_NOTICE = [
        'under_review' => ['application_viewed', 'Application viewed', 'Your application for ":job" at :company is under review.'],
        'shortlisted'  => ['application_shortlisted', 'You have been shortlisted', 'Good news! You were shortlisted for ":job" at :company.'],
        'interview'    => ['application_interview', 'Interview stage', 'You moved to the interview stage for ":job" at :company.'],
        'selected'     => ['application_selected', 'You have been selected', 'Congratulations! You were selected for ":job" at :company.'],
        'rejected'     => ['application_rejected', 'Application update', 'Your application for ":job" at :company was not taken forward.'],
    ];

    public function __construct(private NotificationService $notifications, private FileUploadService $files) {}

    // ------------------------------------------------------------------ candidate
    public function apply(User $candidate, int $jobId, array $d): Application
    {
        $job = Job::published()
            ->whereHas('company', fn ($q) => $q->where('verification_status', 'approved'))
            ->with('company')->findOrFail($jobId);

        if (Application::where('job_id', $job->id)->where('candidate_id', $candidate->id)->exists()) {
            $this->alreadyApplied();
        }

        $resumeId = ! empty($d['resume_id'])
            ? $candidate->resumes()->findOrFail($d['resume_id'])->id
            : $candidate->resumes()->orderByDesc('is_primary')->latest('id')->value('id');

        try {
            $app = DB::transaction(function () use ($candidate, $job, $d, $resumeId) {
                $app = Application::create([
                    'job_id' => $job->id, 'candidate_id' => $candidate->id, 'resume_id' => $resumeId,
                    'cover_letter' => $d['cover_letter'] ?? null, 'status' => 'applied', 'applied_at' => now(),
                ]);
                ApplicationStatusHistory::create([
                    'application_id' => $app->id, 'from_status' => null, 'to_status' => 'applied', 'changed_by' => $candidate->id,
                ]);
                return $app;
            });
        } catch (UniqueConstraintViolationException) {
            $this->alreadyApplied();
        }

        $data = ['application_id' => $app->id, 'job_id' => $job->id];
        $this->notifications->notify($candidate, 'application_submitted', 'Application submitted',
            "You applied for \"{$job->title}\" at {$job->company->company_name}.", $data);
        $this->notifications->notify($job->company->user_id, 'new_application', 'New application',
            "{$candidate->name} applied for \"{$job->title}\".", $data);

        return $app->load('job:id,title,company_id', 'statusHistory');
    }

    public function listForCandidate(User $candidate, array $f = [])
    {
        return $candidate->applications()
            ->with(['job:id,company_id,title,location,employment_type,status', 'job.company:id,company_name,logo', 'statusHistory'])
            ->when(! empty($f['status']), fn ($q) => $q->where('status', $f['status']))
            ->latest('applied_at')->paginate(min(max((int) ($f['per_page'] ?? 15), 1), 50));
    }

    public function withdraw(User $candidate, int $id): Application
    {
        $app = Application::with('job.company')->findOrFail($id);
        Gate::forUser($candidate)->authorize('withdraw', $app);
        if (! isset(self::TRANSITIONS[$app->status])) {
            throw ValidationException::withMessages(['status' => ["A {$app->status} application cannot be withdrawn."]]);
        }
        return $this->change($candidate, $app, 'withdrawn', 'Withdrawn by candidate')->load('statusHistory');
    }

    // ------------------------------------------------------------------ company / admin
    public function listForCompany(User $user, array $f = [])
    {
        $q = Application::query()->with([
            'job:id,company_id,title',
            'candidate:id,name,email,phone',
            'candidate.candidateProfile:id,user_id,profile_photo,city,state,current_job_title,total_experience',
            'resume.file:id,original_name,file_type,file_size',
        ]);

        if ($user->isCompany()) {
            $companyId = $user->company()->firstOrFail()->id;
            $q->whereHas('job', fn ($j) => $j->where('company_id', $companyId));
        }
        $q->when(! empty($f['job_id']), fn ($w) => $w->where('job_id', (int) $f['job_id']))
          ->when(! empty($f['status']), fn ($w) => $w->where('status', $f['status']))
          ->when(! empty($f['search']), function ($w) use ($f) {
              $like = '%'.addcslashes($f['search'], '%_\\').'%';
              $w->whereHas('candidate', fn ($c) => $c->where('name', 'like', $like)->orWhere('email', 'like', $like));
          })
          ->when(! empty($f['skill']), function ($w) use ($f) {
              $names = array_filter(array_map('trim', explode(',', $f['skill'])));
              $w->whereHas('candidate.skills', fn ($s) => $s->whereIn('name', $names));
          })
          ->when(isset($f['min_experience']) && is_numeric($f['min_experience']),
              fn ($w) => $w->whereHas('candidate.candidateProfile', fn ($p) => $p->where('total_experience', '>=', (float) $f['min_experience'])));

        return $q->latest('applied_at')->paginate(min(max((int) ($f['per_page'] ?? 15), 1), 50));
    }

    /** Full application with candidate profile. A company opening a fresh application marks it "viewed". */
    public function view(User $actor, int $id): Application
    {
        $app = Application::with([
            'job.company', 'candidate.candidateProfile', 'candidate.skills:id,name', 'candidate.educations',
            'candidate.experiences', 'candidate.certificates.file', 'resume.file', 'statusHistory',
        ])->findOrFail($id);
        Gate::forUser($actor)->authorize('view', $app);

        if ($actor->isCompany() && $app->status === 'applied') {
            $this->change($actor, $app, 'under_review', 'Viewed by company');
            $app->load('statusHistory');
        }

        $app->setAttribute('resume_download_url', $app->resume?->file ? $this->safeUrl($app->resume->file) : null);
        return $app;
    }

    public function updateStatus(User $actor, int $id, string $to, ?string $note = null): Application
    {
        $app = Application::with('job.company')->findOrFail($id);
        Gate::forUser($actor)->authorize('updateStatus', $app);

        if (! in_array($to, self::TRANSITIONS[$app->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => ["Cannot move an application from {$app->status} to {$to}."]]);
        }
        return $this->change($actor, $app, $to, $note)->load('statusHistory');
    }

    public function invite(User $company, int $candidateId, ?int $jobId, ?string $message): void
    {
        $co = $company->company()->firstOrFail();
        $candidate = User::where('role', 'candidate')->where('status', 'active')->findOrFail($candidateId);
        $job = $jobId ? $co->jobs()->findOrFail($jobId) : null;

        $this->notifications->notify($candidate, 'company_invitation', "{$co->company_name} invited you",
            $message ?: ($job ? "You are invited to apply for \"{$job->title}\"." : 'You have a new invitation.'),
            ['company_id' => $co->id, 'job_id' => $job?->id]);
    }

    // ------------------------------------------------------------------ internals
    private function change(User $actor, Application $app, string $to, ?string $note): Application
    {
        $from = $app->status;
        DB::transaction(function () use ($actor, $app, $from, $to, $note) {
            $app->update(['status' => $to]);
            ApplicationStatusHistory::create([
                'application_id' => $app->id, 'from_status' => $from, 'to_status' => $to,
                'changed_by' => $actor->id, 'note' => $note,
            ]);
        });

        $app->loadMissing('job.company');
        $job = $app->job;
        $data = ['application_id' => $app->id, 'job_id' => $job->id, 'status' => $to];

        if ($to === 'withdrawn') {
            $this->notifications->notify($job->company->user_id, 'application_withdrawn', 'Application withdrawn',
                "A candidate withdrew their application for \"{$job->title}\".", $data);
        } elseif (isset(self::CANDIDATE_NOTICE[$to])) {
            [$type, $title, $msg] = self::CANDIDATE_NOTICE[$to];
            $this->notifications->notify($app->candidate_id, $type, $title,
                strtr($msg, [':job' => $job->title, ':company' => $job->company->company_name]), $data);
        }
        return $app;
    }

    private function safeUrl($file): ?string
    {
        try { return $this->files->temporaryUrl($file, 10); } catch (\Throwable) { return $file->url; }
    }

    private function alreadyApplied(): never
    {
        throw ValidationException::withMessages(['job' => ['You have already applied for this job.']]);
    }
}
