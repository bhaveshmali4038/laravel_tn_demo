@extends('layouts.app')
@php($editing = isset($branch))
@section('title', $editing ? 'Edit branch' : 'Add branch')
@section('content')
    <a class="back-link" href="{{ route('branches.index') }}"><span aria-hidden="true">←</span> All branches</a>
    <div class="page-heading"><div><p class="eyebrow">MAKE ROOM FOR A NEW LOCATION</p><h1>{{ $editing ? 'Edit branch' : 'Add branch' }}<span class="heading-dot">.</span></h1><p class="page-description">Set up a location, its weekly rhythm and any special dates.</p></div></div>
    @if($businesses->isEmpty())
        <section class="surface-card empty-state"><span class="empty-icon" aria-hidden="true">＋</span><h2>Start with a business</h2><p>Every branch belongs to a business. Add your first business to continue.</p><a class="btn btn-primary" href="{{ route('businesses.create') }}">Add business <span aria-hidden="true">→</span></a></section>
    @else
    <form method="post" action="{{ $editing ? route('branches.update', $branch) : route('branches.store') }}" enctype="multipart/form-data" class="needs-app-validation" data-branch-form>
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-lg-8">
                <section class="surface-card form-section mb-4"><div class="section-heading"><span class="step-number">01</span><div><h2>Branch information</h2><p>Connect the location to its business.</p></div></div><div class="row g-4">
                    <div class="col-md-6"><label class="form-label" for="business_id">Business <span class="required-star">*</span></label><select class="form-select @error('business_id') is-invalid @enderror" id="business_id" name="business_id" required><option value="">Choose a business</option>@foreach($businesses as $business)<option value="{{ $business->id }}" @selected(old('business_id', $branch->business_id ?? '') == $business->id)>{{ $business->name }}</option>@endforeach</select><div class="invalid-feedback">Choose an existing business.</div></div>
                    <div class="col-md-6"><label class="form-label" for="name">Branch name <span class="required-star">*</span></label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ is_string(old('name', $branch->name ?? '')) ? old('name', $branch->name ?? '') : '' }}" required maxlength="255" placeholder="e.g. Riverside branch"><div class="invalid-feedback">Enter the branch name.</div></div>
                </div></section>
                <section class="surface-card form-section mb-4"><div class="section-heading"><span class="step-number">02</span><div><h2>Weekly opening hours</h2><p>Enable each available day and add as many time slots as needed.</p></div></div>
                    <div class="hours-helper"><span class="live-dot" aria-hidden="true"></span> All times use {{ config('app.timezone') }}. Unselected days stay closed.</div>
                    <div class="weekday-list">
                    @foreach([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $dayNumber => $dayName)
                        @php($day = is_array(old('weekly_hours.'.$dayNumber, session()->hasOldInput() ? [] : ($initialHours[$dayNumber] ?? []))) ? old('weekly_hours.'.$dayNumber, session()->hasOldInput() ? [] : ($initialHours[$dayNumber] ?? [])) : [])
                        <div class="weekday-row" data-time-group>
                            <div class="weekday-toggle"><input type="hidden" name="weekly_hours[{{ $dayNumber }}][enabled]" value="0"><div class="form-check"><input class="form-check-input" id="weekday-{{ $dayNumber }}" type="checkbox" name="weekly_hours[{{ $dayNumber }}][enabled]" value="1" @checked(($day['enabled'] ?? false)) data-day-toggle><label class="form-check-label" for="weekday-{{ $dayNumber }}">{{ $dayName }}</label></div><span class="day-caption" data-day-caption>Closed</span></div>
                            <div class="weekday-times" data-slot-body><div class="slot-column-labels"><span>Opening time</span><span>Closing time</span></div>@include('components.time-slots', ['prefix' => 'weekly_hours['.$dayNumber.'][slots]', 'slots' => $day['slots'] ?? [], 'idPrefix' => 'weekday-'.$dayNumber, 'label' => $dayName])<button class="btn btn-add-slot mt-2" type="button" data-add-slot><span aria-hidden="true">＋</span> Add time slot</button></div>
                            <span class="closed-day-note" data-closed-note>Unavailable</span>
                        </div>
                    @endforeach
                    </div>
                    <p class="form-text mb-0 mt-3">Slots must start and finish on the same day. A closing time must be later than its opening time; slots cannot overlap.</p>
                </section>
                <section class="surface-card form-section"><div class="section-heading"><span class="step-number">03</span><div><h2>Special dates <span class="optional-label">Optional</span></h2><p>Mark a specific date open or closed for the full calendar day.</p></div></div>
                    <div data-overrides-container>@foreach(is_array(old('overrides', session()->hasOldInput() ? [] : ($initialOverrides ?? []))) ? old('overrides', session()->hasOldInput() ? [] : ($initialOverrides ?? [])) : [] as $overrideIndex => $override)@include('components.date-override', ['index' => $overrideIndex, 'override' => $override])@endforeach</div>
                    <button type="button" class="btn btn-outline-primary" data-add-override><span aria-hidden="true">＋</span> Add special date</button>
                    <template id="date-override-template">@include('components.date-override', ['index' => '__INDEX__', 'override' => []])</template>
                </section>
            </div>
            <div class="col-lg-4"><section class="surface-card form-section mb-4"><div class="section-heading"><span class="step-number">04</span><div><h2>Branch images</h2><p>Show the location at a glance.</p></div></div><label for="images" class="upload-label"><span class="upload-symbol" aria-hidden="true">↑</span><strong>Choose branch images</strong><span>1–10 images · up to 4 MB each</span></label><input class="form-control @error('images') is-invalid @enderror" id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" @required(!$editing) multiple data-image-input data-max-files="10" data-preview-target="images-preview"><div class="invalid-feedback">Choose 1–10 JPG, PNG or WebP images, up to 4 MB each.</div><p class="form-text">JPG, PNG or WebP. You can select multiple images together.</p>@if($editing)<p class="form-text">Leave the upload empty to keep these images. Selecting new images replaces the entire gallery.</p><div class="upload-previews">@foreach($branch->images as $image)<figure><img src="{{ Storage::disk('public')->url($image->path) }}" alt="Current branch image"></figure>@endforeach</div>@endif<div id="images-preview" class="upload-previews" aria-live="polite"></div>@if($errors->any())<p class="form-text">Please select your images again after correcting the form.</p>@endif</section>
                <aside class="aside-note"><span class="section-label">HOW HOURS WORK</span><h3>A weekly routine.<br>A little flexibility.</h3><p>Add separate slots for a lunch break or split shift. Special dates replace the full day's regular schedule: available means open all day; unavailable means closed all day.</p><hr><p class="mb-0">You can add and remove special dates later from the branch details page.</p></aside>
            </div>
        </div>
        <div class="form-actions"><a href="{{ route('branches.index') }}" class="btn btn-light">Cancel</a><button class="btn btn-primary" type="submit">{{ $editing ? 'Save branch' : 'Create branch' }} <span aria-hidden="true">→</span></button></div>
    </form>
    @endif
@endsection
