@extends('layouts.app')
@section('title', 'Branches')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">EVERY LOCATION, IN SYNC</p><h1>Your branches<span class="heading-dot">.</span></h1><p class="page-description">Know what is open. Manage every location and its operating hours.</p></div><a href="{{ route('branches.create') }}" class="btn btn-primary"><span aria-hidden="true">＋</span> Add branch</a></div>
    <div class="overview-strip"><div class="overview-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></div><div><strong>Opening hours that match real life.</strong><p>Weekly schedules, split shifts and date-specific availability, all in one place.</p></div><span class="live-label"><span class="live-dot" aria-hidden="true"></span> LIVE STATUS</span></div>
    <section class="surface-card" aria-labelledby="branches-heading"><div class="card-intro"><div><h2 id="branches-heading">Branch directory</h2><p>Choose a branch to see its hours, images and special dates.</p></div><span class="section-label">ALL LOCATIONS</span></div><div class="table-panel">
        <table class="table align-middle w-100 records-table" id="branches-table" data-records-table="branches" data-source="{{ route('branches.data') }}" aria-label="Branch records"><thead><tr><th>ID</th><th>Branch name</th><th>Business</th><th>Current status</th><th class="text-end">Actions</th></tr></thead><tbody></tbody></table>
        <div class="table-error alert alert-danger d-none" role="alert">We could not load the records. Please refresh the page and try again.</div>
    </div></section>
    <div class="info-note"><span class="note-symbol" aria-hidden="true">i</span><p>Statuses are calculated in {{ config('app.timezone') }} and refresh automatically. A special date takes priority over the regular weekly schedule.</p></div>
@endsection
