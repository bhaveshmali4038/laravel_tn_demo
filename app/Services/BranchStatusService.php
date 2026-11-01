<?php

namespace App\Services;

use App\Models\Branch;
use Carbon\CarbonImmutable;

class BranchStatusService
{
    /**
     * Date exceptions replace the complete weekly schedule for their local date.
     * Available date exceptions are open for the entire local calendar date.
     * Slots include their start and exclude their end: [start_time, end_time).
     */
    public function status(Branch $branch, ?CarbonImmutable $at = null): array
    {
        $now = ($at ?? CarbonImmutable::now())->setTimezone(config('app.timezone'));
        $branch->loadMissing(['timings', 'dateOverrides']);
        $override = $branch->dateOverrides->first(
            fn ($item) => $item->date->format('Y-m-d') === $now->format('Y-m-d')
        );

        $timings = $override ? collect() : $branch->timings->where('weekday', $now->isoWeekday());

        $slots = $override && $override->is_available
            ? [['start_time' => '00:00', 'end_time' => '24:00']]
            : $timings->sortBy('start_time')->map(fn ($timing) => [
            'start_time' => substr($timing->start_time, 0, 5),
            'end_time' => substr($timing->end_time, 0, 5),
        ])->values()->all();

        $clock = $now->format('H:i:s');
        $isOpen = $override ? $override->is_available : $timings->contains(fn ($timing) =>
            $clock >= $timing->start_time && $clock < $timing->end_time
        );

        return [
            'is_open' => $isOpen,
            'label' => $isOpen ? 'Open' : 'Closed',
            'evaluated_at' => $now->toIso8601String(),
            'time_zone' => config('app.timezone'),
            'date' => $now->format('Y-m-d'),
            'source' => $override ? 'date_override' : 'weekly',
            'slots' => $slots,
        ];
    }
}
