@extends('layouts.app')
@php($editing = isset($businessRecord))
@section('title', $editing ? 'Edit business' : 'Add business')
@section('content')
    <a class="back-link" href="{{ route('businesses.index') }}"><span aria-hidden="true">←</span> All businesses</a>
    <div class="page-heading"><div><p class="eyebrow">START WITH THE BUSINESS</p><h1>{{ $editing ? 'Edit business' : 'Add business' }}<span class="heading-dot">.</span></h1><p class="page-description">Add your business details, then create its branches.</p></div><span class="required-note">Contact details are required</span></div>
    <form method="post" action="{{ $editing ? route('businesses.update', $businessRecord) : route('businesses.store') }}" enctype="multipart/form-data" class="needs-app-validation" data-business-form>
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-lg-8"><section class="surface-card form-section">
                <div class="section-heading"><span class="step-number">01</span><div><h2>Business information</h2><p>Contact details used to identify your business.</p></div></div>
                <div class="row g-4">
                    <div class="col-12"><label for="name" class="form-label">Business name <span class="required-star">*</span></label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ is_string(old('name', $businessRecord->name ?? '')) ? old('name', $businessRecord->name ?? '') : '' }}" required maxlength="255" placeholder="e.g. The Corner Collective" autocomplete="organization">@error('name')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">Enter the business name.</div>@enderror</div>
                    <div class="col-md-6"><label for="email" class="form-label">Email address <span class="required-star">*</span></label><input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ is_string(old('email', $businessRecord->email ?? '')) ? old('email', $businessRecord->email ?? '') : '' }}" required maxlength="255" placeholder="hello@example.com" autocomplete="email">@error('email')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">Enter a valid email address.</div>@enderror</div>
                    <div class="col-md-6"><label for="phone_number" class="form-label">Phone number <span class="required-star">*</span></label><input class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" type="tel" name="phone_number" value="{{ is_string(old('phone_number', $businessRecord->phone_number ?? '')) ? old('phone_number', $businessRecord->phone_number ?? '') : '' }}" required maxlength="30" placeholder="+91 98765 43210" autocomplete="tel" data-phone-input>@error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">Enter a phone number containing 7–15 digits.</div>@enderror</div>
                </div>
            </section></div>
            <div class="col-lg-4"><section class="surface-card form-section h-100">
                <div class="section-heading"><span class="step-number">02</span><div><h2>Business logo</h2><p>A recognisable face for your business.</p></div></div>
                <label for="logo" class="upload-label"><span class="upload-symbol" aria-hidden="true">↑</span><strong>Choose a logo</strong><span>JPG, PNG or WebP · up to 4 MB</span></label>
                <input class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" @required(!$editing) data-image-input data-preview-target="logo-preview">
                <div class="invalid-feedback">Choose a JPG, PNG or WebP image up to 4 MB.</div>
                @error('logo')<p class="text-danger small mt-2">{{ $message }}</p>@enderror
                @if($editing)<p class="form-text">Leave the upload empty to keep the current logo.</p><img src="{{ Storage::disk('public')->url($businessRecord->logo_path) }}" alt="Current business logo" width="100" class="rounded my-2">@endif
                <div id="logo-preview" class="upload-previews" aria-live="polite"></div>
                @if($errors->any())<p class="form-text">Please select the logo again after correcting the form.</p>@endif
            </section></div>
        </div>
        <div class="form-actions"><a href="{{ route('businesses.index') }}" class="btn btn-light">Cancel</a><button type="submit" class="btn btn-primary">{{ $editing ? 'Save business' : 'Create business' }} <span aria-hidden="true">→</span></button></div>
    </form>
@endsection
