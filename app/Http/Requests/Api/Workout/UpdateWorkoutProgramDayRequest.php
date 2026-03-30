<?php

namespace App\Http\Requests\Api\Workout;

use App\Models\WorkoutProgramDay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkoutProgramDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var WorkoutProgramDay $day */
        $day = $this->route('day');

        return [
            'day_number' => [
                'required',
                'integer',
                'min:1',
                'max:7',
                Rule::unique('workout_program_days', 'day_number')
                    ->where(fn ($query) => $query->where('program_id', $day->program_id))
                    ->ignore($day->id),
            ],
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
