<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EducationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'institution' => ['required', 'string', 'max:190'],
            'degree' => ['required', 'string', 'max:190'],
            'field' => ['nullable', 'string', 'max:190'],
            'start_year' => ['nullable', 'integer', 'between:1950,2100'],
            'end_year' => ['nullable', 'integer', 'between:1950,2100', 'gte:start_year'],
        ];
    }
}
