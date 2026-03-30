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
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'default_sets' => ['required', 'integer', 'gt:0'],
            'default_reps' => ['required', 'string', 'max:100'],
            'default_tempo' => ['required', 'string', 'max:100'],
            'default_rest' => ['required', 'integer', 'min:0'],
            'variations' => ['nullable', 'array'],
            'variations.*.id' => ['nullable', 'integer'],
            'variations.*.variation_name' => ['required_with:variations', 'string', 'max:255'],
        ];
    }
}
