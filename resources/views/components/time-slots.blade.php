@php($slotRows = is_array($slots ?? null) && count($slots) ? $slots : [['start_time' => '', 'end_time' => '']])
<div class="time-slots" data-slots-container data-slot-prefix="{{ $prefix }}">
    @foreach($slotRows as $slotIndex => $slot)
        @php($slot = is_array($slot) ? $slot : [])
        <div class="time-slot" data-time-slot>
            <div class="time-field"><label class="visually-hidden" for="{{ $idPrefix }}-{{ $slotIndex }}-start">{{ $label }} opening time</label><input class="form-control" type="time" id="{{ $idPrefix }}-{{ $slotIndex }}-start" name="{{ $prefix }}[{{ $slotIndex }}][start_time]" value="{{ is_string($slot['start_time'] ?? null) ? substr($slot['start_time'], 0, 5) : '' }}" data-start-time required><div class="invalid-feedback">Enter a valid opening time.</div></div>
            <span class="time-separator" aria-hidden="true">–</span>
            <div class="time-field"><label class="visually-hidden" for="{{ $idPrefix }}-{{ $slotIndex }}-end">{{ $label }} closing time</label><input class="form-control" type="time" id="{{ $idPrefix }}-{{ $slotIndex }}-end" name="{{ $prefix }}[{{ $slotIndex }}][end_time]" value="{{ is_string($slot['end_time'] ?? null) ? substr($slot['end_time'], 0, 5) : '' }}" data-end-time required><div class="invalid-feedback">Closing time must follow opening time without overlapping another slot.</div></div>
            <button type="button" class="btn btn-slot-remove" data-remove-slot aria-label="Remove time slot"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 6h12M8 3h4l1 3H7l1-3ZM6 6l1 11h6l1-11M9 9v5M11 9v5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        </div>
    @endforeach
</div>
