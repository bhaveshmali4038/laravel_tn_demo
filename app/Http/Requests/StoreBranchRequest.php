<?php

namespace App\Http\Requests;

use App\Services\ScheduleValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'name' => ['required', 'string', 'max:255'],
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'weekly_hours' => ['nullable', 'array:1,2,3,4,5,6,7'],
            'weekly_hours.*' => ['array:enabled,slots'],
            'weekly_hours.*.enabled' => ['sometimes', 'boolean'],
            'weekly_hours.*.slots' => ['sometimes', 'array'],
            'weekly_hours.*.slots.*' => ['array:start_time,end_time'],
            'weekly_hours.*.slots.*.start_time' => ['required', 'date_format:H:i'],
            'weekly_hours.*.slots.*.end_time' => ['required', 'date_format:H:i'],
            'overrides' => ['nullable', 'array'],
            'overrides.*' => ['array:date,is_available'],
            'overrides.*.date' => ['required', 'date_format:Y-m-d', 'distinct'],
            'overrides.*.is_available' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $weekly = $this->input('weekly_hours', []);

            foreach (is_array($weekly) ? $weekly : [] as $weekday => $day) {
                if (! is_array($day)) {
                    continue;
                }

                $enabled = in_array($day['enabled'] ?? null, [true, 1, '1'], true);
                $slots = $day['slots'] ?? [];
                if ($enabled && (! is_array($slots) || count($slots) === 0)) {
                    $validator->errors()->add("weekly_hours.{$weekday}.slots", 'An available weekday needs at least one time slot.');
                }
                if (! $enabled && is_array($slots) && count($slots) > 0) {
                    $validator->errors()->add("weekly_hours.{$weekday}.slots", 'An unavailable weekday cannot contain time slots.');
                }
                if (is_array($slots)) {
                    ScheduleValidation::slots($validator, $slots, "weekly_hours.{$weekday}.slots");
                }
            }

        });
    }
}
