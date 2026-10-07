<?php

namespace App\Http\Requests;

use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $req = $this->isMethod('post') ? 'required' : 'sometimes';
        return [
            'title' => [$req, 'string', 'max:190'],
            'description' => [$req, 'string'],
            'requirements' => ['nullable', 'string'],
            'location' => [$req, 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'employment_type' => [$req, Rule::in(Job::TYPES)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'experience_min' => ['nullable', 'numeric', 'min:0'],
            'experience_max' => ['nullable', 'numeric', 'gte:experience_min'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'gte:salary_min'],
            'vacancy_count' => ['nullable', 'integer', 'min:1'],
            'application_deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'closed'])],
            'skills' => ['nullable', 'array', 'max:30'],
            'skills.*' => ['string', 'max:60'],
        ];
    }
}
