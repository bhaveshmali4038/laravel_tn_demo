<?php

namespace App\Services;

use Illuminate\Validation\Validator;

class ScheduleValidation
{
    /** Add cross-field rules after Laravel has checked individual time values. */
    public static function slots(Validator $validator, array $slots, string $prefix): void
    {
        $valid = [];

        foreach ($slots as $index => $slot) {
            if (! is_array($slot)) {
                continue;
            }

            $start = $slot['start_time'] ?? null;
            $end = $slot['end_time'] ?? null;

            if (! is_string($start) || ! is_string($end)
                || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', $start)
                || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', $end)) {
                continue;
            }

            if ($start >= $end) {
                $validator->errors()->add("{$prefix}.{$index}.end_time", 'The end time must be after the start time on the same day.');
                continue;
            }

            $valid[] = ['start' => $start, 'end' => $end, 'index' => $index];
        }

        usort($valid, fn ($a, $b) => strcmp($a['start'], $b['start']));
        $latestEnd = null;

        foreach ($valid as $slot) {
            if ($latestEnd !== null && $slot['start'] < $latestEnd) {
                $validator->errors()->add("{$prefix}.{$slot['index']}.start_time", 'Time slots on the same day must not overlap.');
            }

            $latestEnd = max($latestEnd ?? $slot['end'], $slot['end']);
        }
    }

}
