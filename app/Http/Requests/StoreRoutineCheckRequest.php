<?php

namespace App\Http\Requests;

use App\Models\Routine;
use App\Models\RoutineCheck;
use App\Models\RoutineWeek;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class StoreRoutineCheckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // The 'auth' middleware already checks login; single-user app.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'routine_id' => ['required', 'integer', 'exists:routines,id'],
            'done_on' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',                                  // future days are locked
                'after_or_equal:'.today()->startOfWeek()->toDateString(), // old weeks are read-only
            ],
        ];
    }

    /**
     * Custom messages for the date rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'done_on.before_or_equal' => 'You cannot tick a future day.',
            'done_on.after_or_equal' => 'Old weeks are read-only.',
        ];
    }

    /**
     * Checks that need the database. They run after rules().
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Basic rules failed → no need to look further.
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $routine = Routine::find($this->integer('routine_id'));
                $day = Carbon::parse($this->string('done_on')->toString());

                // Plan rule 5: a routine with sub-tasks is done automatically, never ticked directly.
                if ($routine === null || $routine->children()->exists()) {
                    $validator->errors()->add('routine_id', 'Tick its sub-tasks instead.');

                    return;
                }

                // The routine (or its parent, for a sub-task) must be on that week's sheet.
                $week = RoutineWeek::forDate($day);
                $topLevelId = $routine->parent_id ?? $routine->id;
                if ($week === null || ! $week->routines()->whereKey($topLevelId)->exists()) {
                    $validator->errors()->add('routine_id', "This routine is not on this week's sheet.");

                    return;
                }

                // Plan rule 4: once per day (the database's unique index is the final safety net).
                $alreadyTicked = RoutineCheck::where('routine_id', $routine->id)->whereDate('done_on', $day)->exists();
                if ($alreadyTicked) {
                    $validator->errors()->add('done_on', 'Already ticked for this day.');
                }
            },
        ];
    }
}
