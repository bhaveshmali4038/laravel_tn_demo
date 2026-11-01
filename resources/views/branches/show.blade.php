@extends('layouts.app')
@section('title', $branch->name)
@section('content')
    <a class="back-link" href="{{ route('branches.index') }}"><span aria-hidden="true">←</span> All branches</a>
    <div class="page-heading branch-page-heading"><div><p class="eyebrow">{{ $branch->business->name }}</p><h1>{{ $branch->name }}<span class="heading-dot">.</span></h1><p class="page-description">Branch details, opening hours and date-specific availability.</p></div><div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-primary" href="{{ route('branches.edit', $branch) }}">Edit branch</a><form method="post" action="{{ route('branches.destroy', $branch) }}" data-delete-form data-confirm="Delete this branch, its hours, special dates and uploaded images?">@csrf @method('DELETE')<button type="submit" class="btn btn-outline-danger">Delete branch</button></form></div></div>
    <div class="row g-4">
        <div class="col-lg-7">
            <section class="surface-card hours-card mb-4" data-live-status data-status-url="{{ route('branches.status', $branch) }}">
                <div class="hours-card-header"><div><span class="eyebrow">RIGHT NOW</span><h2>Opening hours</h2></div><span class="status-badge {{ $status['is_open'] ? 'is-open' : 'is-closed' }}" data-status-label><span aria-hidden="true">●</span> {{ $status['label'] }}</span></div>
                <div class="today-summary"><div><span class="section-label">TODAY <span data-status-date>{{ $status['date'] }}</span></span><p data-today-slots>@if($status['source'] === 'date_override' && $status['is_open'])<span class="time-pill">Open all day · 00:00 – 24:00</span>@else @forelse($status['slots'] as $slot)<span class="time-pill">{{ $slot['start_time'] }} – {{ $slot['end_time'] }}</span>@empty<span class="text-secondary">Closed all day</span>@endforelse @endif</p></div><span class="special-date-indicator {{ $status['source'] === 'date_override' ? '' : 'd-none' }}" data-status-override>Special date</span></div>
                <div class="weekly-display">
                    @foreach([7 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'] as $dayNumber => $dayName)
                    @php($hours = $weeklyHours->get($dayNumber, collect()))
                    <div class="weekly-display-row {{ now(config('app.timezone'))->isoWeekday() === $dayNumber ? 'is-today' : '' }}" data-weekday="{{ $dayNumber }}"><div class="week-label">{{ $dayName }}<span class="today-label">Today</span></div><div class="week-slots">@forelse($hours as $timing)<span>{{ substr($timing->start_time, 0, 5) }} – {{ substr($timing->end_time, 0, 5) }}</span>@empty<span class="closed-hours">Closed</span>@endforelse</div></div>
                    @endforeach
                </div>
                <div class="status-footnote"><span class="live-dot" aria-hidden="true"></span><span data-status-check>Checked {{ \Carbon\CarbonImmutable::parse($status['evaluated_at'])->format('h:i:s A') }} · {{ $status['time_zone'] }}</span><span class="status-refresh-error d-none" data-status-error>Refresh paused. Reload to check status.</span></div>
            </section>
            <section class="surface-card form-section"><div class="section-heading"><span class="step-number">＋</span><div><h2>Add a special date</h2><p>Override the full calendar day, midnight to midnight.</p></div></div>
                <form method="post" action="{{ route('branches.date-overrides.store', $branch) }}" class="needs-app-validation" data-override-form data-time-group data-existing-dates="{{ $branch->dateOverrides->map(fn ($item) => $item->date->format('Y-m-d'))->values()->toJson() }}">
                    @csrf
                    <div class="row g-3 mb-3"><div class="col-sm-6"><label for="date" class="form-label">Date <span class="required-star">*</span></label><input class="form-control" type="date" id="date" name="date" value="{{ is_string(old('date')) ? old('date') : '' }}" required data-override-date><div class="invalid-feedback">Choose a date without an existing override.</div></div><div class="col-sm-6"><label for="is_available" class="form-label">Availability</label><select class="form-select" id="is_available" name="is_available" data-availability-select><option value="0" @selected(old('is_available', '0') == '0')>Unavailable · closed all day</option><option value="1" @selected(old('is_available', '0') == '1')>Available · open all day</option></select></div></div>
                    <p class="form-text" data-open-note>The branch will be open all day, midnight to midnight in {{ config('app.timezone') }}.</p>
                    <p class="closed-day-note" data-closed-note>The branch will be closed for the entire date.</p>
                    <div class="mt-4"><button type="submit" class="btn btn-primary">Add special date</button></div>
                </form>
            </section>
        </div>
        <div class="col-lg-5">
            <section class="surface-card branch-gallery mb-4" aria-labelledby="gallery-title"><div class="card-intro"><div><h2 id="gallery-title">At this location</h2><p>{{ $branch->images->count() }} {{ \Illuminate\Support\Str::plural('image', $branch->images->count()) }}</p></div></div><div class="gallery-grid">@foreach($branch->images as $branchImage)<a href="{{ asset('storage/'.$branchImage->path) }}" target="_blank" rel="noopener" class="gallery-image"><img src="{{ asset('storage/'.$branchImage->path) }}" alt="{{ $branch->name }} — image {{ $loop->iteration }}" loading="lazy"><span class="visually-hidden">Open full image in a new tab</span></a>@endforeach</div></section>
            <section class="surface-card special-dates-card mb-4" aria-labelledby="dates-heading"><div class="card-intro"><div><h2 id="dates-heading">Special dates</h2><p>These replace the regular hours for the date.</p></div><span class="count-chip">{{ $branch->dateOverrides->count() }}</span></div><div class="special-date-list">
                @forelse($branch->dateOverrides->sortBy('date') as $override)
                <div class="special-date-row"><div><strong>{{ \Carbon\CarbonImmutable::parse($override->date)->format('d M Y') }}</strong><span class="override-state {{ $override->is_available ? 'text-success' : 'text-secondary' }}">{{ $override->is_available ? 'Available · open all day' : 'Unavailable · closed all day' }}</span></div><form method="post" action="{{ route('branches.date-overrides.destroy', [$branch, $override]) }}" data-delete-form data-confirm="Delete this special date? The regular weekly hours will apply again.">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete special date {{ \Carbon\CarbonImmutable::parse($override->date)->format('d M Y') }}">Delete</button></form></div>
                @empty<div class="small-empty-state"><span aria-hidden="true">○</span><strong>No special dates yet</strong><p>The regular weekly schedule applies every week.</p></div>@endforelse
            </div></section>
            <section class="business-contact"><span class="section-label">PART OF THE BUSINESS</span><div class="business-contact-heading"><img src="{{ asset('storage/'.$branch->business->logo_path) }}" alt="{{ $branch->business->name }} logo"><div><strong>{{ $branch->business->name }}</strong><a href="mailto:{{ $branch->business->email }}">{{ $branch->business->email }}</a><a href="tel:{{ preg_replace('/[^+0-9]/', '', $branch->business->phone_number) }}">{{ $branch->business->phone_number }}</a></div></div></section>
        </div>
    </div>
@endsection
