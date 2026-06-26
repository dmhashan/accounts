<?php

namespace App\Http\Requests\Api\Workout;

use App\Models\ExerciseVariation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWorkoutDayExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exercise_id' => ['required', 'integer', 'exists:exercises,id'],
            'w1_w3_exercise' => ['nullable', 'string', 'max:255'],
            'w2_w4_exercise' => ['nullable', 'string', 'max:255'],
            'sets' => ['required', 'integer', 'gt:0'],
            'reps' => ['required', 'string', 'max:100'],
            'tempo' => ['required', 'string', 'max:50'],
            'rest_seconds' => ['required', 'integer', 'min:0'],
            'exercise_order' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $exerciseId = (int) $this->input('exercise_id');
            $w1w3 = trim((string) $this->input('w1_w3_exercise', ''));
            $w2w4 = trim((string) $this->input('w2_w4_exercise', ''));

            if ($exerciseId <= 0 || $w1w3 === '' || $w2w4 === '') {
                return;
            }

            $tenantId = app('tenant')->id;
            $variationNames = ExerciseVariation::query()
                ->whereHas('exercise', fn ($query) => $query->where('id', $exerciseId))
                ->pluck('variation_name')
                ->map(fn ($name) => trim((string) $name))
                ->all();

            if (!in_array($w1w3, $variationNames, true)) {
                $validator->errors()->add('w1_w3_exercise', 'Selected W1/W3 variation is invalid for the chosen exercise.');
            }

            if (!in_array($w2w4, $variationNames, true)) {
                $validator->errors()->add('w2_w4_exercise', 'Selected W2/W4 variation is invalid for the chosen exercise.');
            }
        });
    }
}
