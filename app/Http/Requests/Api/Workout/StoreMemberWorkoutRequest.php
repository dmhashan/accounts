<?php

namespace App\Http\Requests\Api\Workout;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:program,file,text'],
            'title' => ['nullable', 'string', 'max:255'],
            'effective_date' => ['required', 'date'],
            'program_id' => ['required_if:type,program', 'nullable', 'integer', 'exists:workout_programs,id'],
            'program_title_override' => ['nullable', 'string', 'max:255'],
            'program_description_override' => ['nullable', 'string', 'max:5000'],
            'file' => [
                'required_if:type,file',
                'nullable',
                'file',
                'max:8192', // 8MB
                'mimes:pdf,jpg,jpeg,png,webp',
            ],
            'formatted_text' => ['required_if:type,text', 'nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required_if' => 'Please select a workout file (PDF or image) to upload.',
            'file.mimes' => 'The workout file must be a PDF or image file (pdf, jpg, jpeg, png, webp).',
            'file.max' => 'The workout file may not be greater than 8MB. Please compress or choose a smaller file.',
            'formatted_text.required_if' => 'Please enter the formatted workout routine content.',
            'program_id.required_if' => 'Please select a configured workout program.',
            'effective_date.required' => 'Please select an effective start date.',
            'effective_date.date' => 'Please provide a valid start date.',
        ];
    }
}
