<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Application\ApplicationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Candidate;
use App\Http\Controllers\Company;
use App\Http\Controllers\File\FileController;
use App\Http\Controllers\Job\JobController;
use App\Http\Controllers\Notification\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json(['success' => true, 'message' => 'pong', 'data' => null]));

// ---------- Public auth ----------
Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// ---------- Public job browsing ----------
Route::middleware('throttle:api')->group(function () {
    Route::get('jobs', [JobController::class, 'index']);
    Route::get('jobs/{id}', [JobController::class, 'show'])->whereNumber('id');
});

// ---------- Authenticated ----------
Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::put('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::put('notifications/{id}/read', [NotificationController::class, 'read']);

    // Files
    Route::post('files/upload', [FileController::class, 'upload'])->middleware('throttle:uploads');
    Route::delete('files/{id}', [FileController::class, 'destroy']);

    // Applications (shared; policy decides who sees what)
    Route::get('applications', [ApplicationController::class, 'index']);
    Route::get('applications/{id}', [ApplicationController::class, 'show']);
    Route::put('applications/{id}/status', [ApplicationController::class, 'updateStatus'])->middleware('role:company,admin');
    Route::post('jobs/{job}/apply', [ApplicationController::class, 'apply'])->middleware('role:candidate');
    Route::put('applications/{id}/withdraw', [ApplicationController::class, 'withdraw'])->middleware('role:candidate');

    // Job write operations (company owner or admin)
    Route::middleware('role:company,admin')->group(function () {
        Route::post('jobs', [JobController::class, 'store']);
        Route::put('jobs/{id}', [JobController::class, 'update']);
        Route::delete('jobs/{id}', [JobController::class, 'destroy']);
    });

    // ---------- Candidate ----------
    Route::prefix('candidate')->middleware('role:candidate')->group(function () {
        Route::get('profile', [Candidate\CandidateProfileController::class, 'show']);
        Route::put('profile', [Candidate\CandidateProfileController::class, 'update']);

        Route::get('education', [Candidate\EducationController::class, 'index']);
        Route::post('education', [Candidate\EducationController::class, 'store']);
        Route::put('education/{id}', [Candidate\EducationController::class, 'update']);
        Route::delete('education/{id}', [Candidate\EducationController::class, 'destroy']);

        Route::get('experience', [Candidate\ExperienceController::class, 'index']);
        Route::post('experience', [Candidate\ExperienceController::class, 'store']);
        Route::put('experience/{id}', [Candidate\ExperienceController::class, 'update']);
        Route::delete('experience/{id}', [Candidate\ExperienceController::class, 'destroy']);

        Route::get('skills', [Candidate\CandidateSkillController::class, 'index']);
        Route::post('skills', [Candidate\CandidateSkillController::class, 'store']);

        Route::post('resume', [Candidate\CandidateFileController::class, 'resume'])->middleware('throttle:uploads');
        Route::post('certificates', [Candidate\CandidateFileController::class, 'certificate'])->middleware('throttle:uploads');

        Route::get('applications', [Candidate\CandidateApplicationController::class, 'index']);
    });

    // ---------- Company ----------
    Route::prefix('company')->middleware('role:company')->group(function () {
        Route::get('profile', [Company\CompanyProfileController::class, 'show']);
        Route::put('profile', [Company\CompanyProfileController::class, 'update']);

        Route::get('jobs', [Company\CompanyJobController::class, 'index']);
        Route::post('jobs', [Company\CompanyJobController::class, 'store']);
        Route::get('jobs/{id}', [Company\CompanyJobController::class, 'show']);
        Route::put('jobs/{id}', [Company\CompanyJobController::class, 'update']);
        Route::delete('jobs/{id}', [Company\CompanyJobController::class, 'destroy']);

        Route::get('applicants', [Company\ApplicantController::class, 'index']);
        Route::get('applicants/{id}', [Company\ApplicantController::class, 'show']);
        Route::post('invitations', [Company\ApplicantController::class, 'invite']);
        Route::put('applications/{id}/status', [ApplicationController::class, 'updateStatus']);
    });

    // ---------- Admin ----------
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('dashboard', [Admin\DashboardController::class, 'index']);
        Route::get('users', [Admin\AdminUserController::class, 'index']);
        Route::put('users/{id}/block', [Admin\AdminUserController::class, 'block']);
        Route::put('users/{id}/unblock', [Admin\AdminUserController::class, 'unblock']);
        Route::get('companies', [Admin\AdminCompanyController::class, 'index']);
        Route::put('companies/{id}/approve', [Admin\AdminCompanyController::class, 'approve']);
        Route::put('companies/{id}/reject', [Admin\AdminCompanyController::class, 'reject']);
        Route::put('companies/{id}/block', [Admin\AdminCompanyController::class, 'block']);
        Route::get('jobs', [Admin\AdminJobController::class, 'index']);
        Route::put('jobs/{id}/publish', [Admin\AdminJobController::class, 'publish']);
        Route::put('jobs/{id}/unpublish', [Admin\AdminJobController::class, 'unpublish']);
        Route::delete('jobs/{id}', [Admin\AdminJobController::class, 'destroy']);
        Route::get('applications', [Admin\AdminApplicationController::class, 'index']);
        Route::get('files', [Admin\AdminFileController::class, 'index']);
        Route::delete('files/{id}', [Admin\AdminFileController::class, 'destroy']);
        Route::get('notifications', [Admin\AdminNotificationController::class, 'index']);
        Route::post('notifications', [Admin\AdminNotificationController::class, 'store']);
        Route::delete('notifications/{id}', [Admin\AdminNotificationController::class, 'destroy']);
        Route::apiResource('skills', Admin\AdminSkillController::class)->except('show');
        Route::apiResource('categories', Admin\AdminCategoryController::class)->except('show');
    });
});
