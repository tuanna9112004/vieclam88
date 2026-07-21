<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Dashboard HR — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5">
            <h1 class="h4">Xin chào, {{ auth()->user()->name }}</h1>

            <a href="{{ route('hr.jobs.index') }}" class="btn btn-outline-primary mt-3">Việc làm</a>
            <a href="{{ route('hr.companies.index') }}" class="btn btn-outline-primary mt-3">Công ty</a>

            @if (auth()->user()->isAdmin())
                <a href="{{ route('hr.industrial-parks.index') }}" class="btn btn-outline-primary mt-3">Khu công nghiệp</a>
                <a href="{{ route('hr.branches.index') }}" class="btn btn-outline-primary mt-3">Cơ sở</a>
            @endif

            <form method="POST" action="{{ route('hr.logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Đăng xuất</button>
            </form>
        </div>
    </body>
</html>
