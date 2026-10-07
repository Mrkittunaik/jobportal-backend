<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'resume_id' => ['nullable', 'integer', 'exists:resumes,id'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
