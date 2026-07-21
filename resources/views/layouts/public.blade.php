<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'vieclam88'))</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-light">
    <header class="border-bottom bg-white">
        <div class="container py-3">
            <a href="{{ route('jobs.index') }}" class="fs-4 fw-bold text-decoration-none text-dark">
                {{ config('app.name', 'vieclam88') }}
            </a>
        </div>
    </header>

    <main class="py-4">
        @if (session('success'))
            <div class="container">
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="container">
                <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
