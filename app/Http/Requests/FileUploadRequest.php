<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileUploadRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $cat = $this->input('category', 'other');
        $cfg = config("uploads.categories.$cat", config('uploads.categories.other'));
        return [
            'category' => ['required', Rule::in(File::CATEGORIES)],
            'file' => ['required', 'file', 'mimes:'.implode(',', $cfg['mimes']), 'max:'.$cfg['max_kb']],
        ];
    }
}
