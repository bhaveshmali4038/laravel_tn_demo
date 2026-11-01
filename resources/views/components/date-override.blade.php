@php($override = is_array($override ?? null) ? $override : [])
<div class="override-card" data-time-group data-override-card>
    <div class="override-heading"><strong>Special date</strong><button type="button" class="btn btn-sm btn-outline-danger" data-remove-override>Remove date</button></div>
    <div class="row g-3 mb-3">
        <div class="col-sm-6"><label class="form-label" for="override-{{ $index }}-date">Date <span class="required-star">*</span></label><input class="form-control" type="date" id="override-{{ $index }}-date" name="overrides[{{ $index }}][date]" value="{{ is_string($override['date'] ?? null) ? $override['date'] : '' }}" required data-override-date><div class="invalid-feedback">Choose a unique date.</div></div>
        <div class="col-sm-6"><label class="form-label" for="override-{{ $index }}-availability">Availability <span class="required-star">*</span></label><select class="form-select" id="override-{{ $index }}-availability" name="overrides[{{ $index }}][is_available]" data-availability-select><option value="0" @selected(($override['is_available'] ?? '0') == '0')>Unavailable · closed all day</option><option value="1" @selected(($override['is_available'] ?? '0') == '1')>Available · open all day</option></select></div>
    </div>
    <p class="form-text mb-0" data-open-note>This branch will be open all day, midnight to midnight in {{ config('app.timezone') }}.</p>
    <p class="closed-day-note mb-0" data-closed-note>This branch will be closed for the entire date.</p>
</div>
