<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchDateOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['bail', 'required', 'date_format:Y-m-d', function ($attribute, $value, $fail) {
                if ($this->route('branch')->dateOverrides()->whereDate('date', $value)->exists()) {
                    $fail('This branch already has availability set for this date. Delete that record before creating another.');
                }
            }],
            'is_available' => ['required', 'boolean'],
            'slots' => ['prohibited'],
        ];
    }

}
