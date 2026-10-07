<?php

// Upload rules per file category (size in KB). Used by FileUploadService + FileUploadRequest.
return [
    'disk' => env('UPLOAD_DISK', 'r2'),

    'categories' => [
        'profile_photo' => ['mimes' => ['jpg', 'jpeg', 'png', 'webp'], 'max_kb' => 2048],
        'company_logo'  => ['mimes' => ['jpg', 'jpeg', 'png', 'webp', 'svg'], 'max_kb' => 2048],
        'job_image'     => ['mimes' => ['jpg', 'jpeg', 'png', 'webp'], 'max_kb' => 4096],
        'resume'        => ['mimes' => ['pdf', 'doc', 'docx'], 'max_kb' => 5120],
        'certificate'   => ['mimes' => ['pdf', 'jpg', 'jpeg', 'png'], 'max_kb' => 5120],
        'video'         => ['mimes' => ['mp4', 'webm'], 'max_kb' => 51200],
        'other'         => ['mimes' => ['pdf', 'jpg', 'jpeg', 'png'], 'max_kb' => 5120],
    ],
];
