@extends('layouts.app')
@section('title', 'Businesses')
@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">YOUR ORGANISATION</p><h1>Businesses<span class="heading-dot">.</span></h1><p class="page-description">The businesses behind every branch. Keep their details in one place.</p></div>
        <a href="{{ route('businesses.create') }}" class="btn btn-primary"><span aria-hidden="true">＋</span> Add business</a>
    </div>
    <section class="surface-card" aria-labelledby="businesses-heading">
        <div class="card-intro"><div><h2 id="businesses-heading">Business directory</h2><p>Search, sort and browse your businesses.</p></div><span class="section-label">ALL BUSINESSES</span></div>
        <div class="table-panel">
            <table class="table align-middle w-100 records-table" id="businesses-table" data-records-table="businesses" data-source="{{ route('businesses.data') }}" aria-label="Business records">
                <thead><tr><th>ID</th><th>Logo</th><th>Business name</th><th>Email</th><th>Phone number</th><th>Branches</th><th class="text-end">Actions</th></tr></thead>
                <tbody></tbody>
            </table>
            <div class="table-error alert alert-danger d-none" role="alert">We could not load the records. Please refresh the page and try again.</div>
        </div>
    </section>
    <div class="info-note"><span class="note-symbol" aria-hidden="true">i</span><p>Each business can have multiple branches with their own opening hours. Deleting a business also deletes all its branches and uploaded images.</p></div>
@endsection
