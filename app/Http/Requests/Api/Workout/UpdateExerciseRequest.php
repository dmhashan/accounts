<?php

namespace App\Http\Requests\Api\Workout;

use App\Models\Exercise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant')->id;
        /** @var Exercise $exercise */
        $exercise = $this->route('exercise');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('exercises')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($exercise->id),
            ],
            'muscle_group' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['compound', 'isolation'])],
            'equipment' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
