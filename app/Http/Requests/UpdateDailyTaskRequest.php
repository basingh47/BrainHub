<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyTaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'in:office,learning,personal'],
            'priority' => ['required', 'in:high,medium,low'],
            // No "after_or_equal:today": an overdue task must still be editable.
            'due_date' => ['required', 'date'],
            'due_time' => ['nullable', 'date_format:H:i'],
            // Only on edit: a new task always starts as 'pending'.
            'status' => ['required', 'in:pending,in_progress,completed'],
        ];
    }
}
