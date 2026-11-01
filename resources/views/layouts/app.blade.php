<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Business Timing') · Branch Hours</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <header class="site-header">
        <nav class="navbar navbar-expand-md container-xl" aria-label="Main navigation">
            <a class="navbar-brand brand-lockup" href="{{ route('branches.index') }}">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 10h14v10H5zM3 10l2-6h14l2 6M9 20v-6h6v6M3 10c0 2 4 2 4 0 0 2 5 2 5 0 0 2 5 2 5 0 0 2 4 2 4 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>Branch<span class="brand-light">Hours</span><small>BUSINESS TIMING MANAGER</small></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-navigation" aria-controls="main-navigation" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="main-navigation">
                <div class="navbar-nav ms-auto align-items-md-center gap-md-2">
                    <a class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}" @if(request()->routeIs('branches.*')) aria-current="page" @endif>Branches</a>
                    <a class="nav-link {{ request()->routeIs('businesses.*') ? 'active' : '' }}" href="{{ route('businesses.index') }}" @if(request()->routeIs('businesses.*')) aria-current="page" @endif>Businesses</a>
                    <span class="timezone-chip ms-md-3"><span class="live-dot" aria-hidden="true"></span>{{ config('app.timezone') }}</span>
                </div>
            </div>
        </nav>
    </header>
    <main class="container-xl main-content" id="main-content">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="status">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger validation-summary" role="alert" tabindex="-1">
                <strong>Please check the following information.</strong>
                <ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
    <footer class="site-footer container-xl"><span>BranchHours <span class="footer-divider">/</span> Business management, on time.</span><span>Times shown in {{ config('app.timezone') }}</span></footer>
    @stack('scripts')
</body>
</html>
