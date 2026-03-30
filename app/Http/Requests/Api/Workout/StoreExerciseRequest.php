<?php

namespace App\Http\Requests\Api\Workout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('exercises')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
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
