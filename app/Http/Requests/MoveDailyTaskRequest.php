<?php

namespace App\Http\Requests;

use App\Models\DailyTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MoveDailyTaskRequest extends FormRequest
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
            // Plan rule 3: the new date must be today or later.
            'due_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * Extra checks that run after rules() — they need the task from the DB.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // {daily_task} from the URL /daily-tasks/{daily_task}/move
                $task = DailyTask::find($this->route('daily_task'));

                // Plan rule 4: a completed task cannot be carried forward.
                if ($task?->status === 'completed') {
                    $validator->errors()->add('due_date', 'A completed task cannot be moved.');
                }
            },
        ];
    }
}
