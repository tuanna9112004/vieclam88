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
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', config('app.name', 'vieclam88'))">
    @hasSection('meta_description')
        <meta property="og:description" content="@yield('meta_description')">
    @endif
    @hasSection('canonical')
        <meta property="og:url" content="@yield('canonical')">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="public-shell bg-light d-flex flex-column min-vh-100">
    <header class="pub-header border-bottom">
        <div class="container py-2">
            <div class="d-flex align-items-center justify-content-between gap-3" style="min-height: 56px;">
                <a href="{{ route('home') }}" class="pub-header__brand text-decoration-none">
                    {{ config('app.name', 'vieclam88') }}
                </a>

                <nav class="d-none d-lg-flex align-items-center gap-3" aria-label="Điều hướng chính">
                    <a href="{{ route('home') }}" class="nav-link text-dark">Trang chủ</a>
                    <a href="{{ route('jobs.index') }}" class="nav-link text-dark">Việc làm</a>
                    <a href="{{ route('companies.index') }}" class="nav-link text-dark">Công ty</a>
                    <a href="{{ route('contact.show') }}" class="nav-link text-dark">Liên hệ</a>
                    <a href="{{ route('jobs.index') }}" class="btn btn-primary fw-semibold" style="min-height:48px">
                        Tìm việc ngay
                    </a>
                </nav>

                <button
                    class="btn btn-outline-secondary d-lg-none"
                    type="button"
                    style="min-height:48px; min-width:48px"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#pub-mobile-nav"
                    aria-controls="pub-mobile-nav"
                    aria-label="Mở menu"
                >
                    <span aria-hidden="true">&#9776;</span>
                </button>
            </div>
        </div>
    </header>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="pub-mobile-nav" aria-labelledby="pub-mobile-nav-label">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title h5" id="pub-mobile-nav-label">{{ config('app.name', 'vieclam88') }}</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Đóng menu"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="d-flex flex-column gap-2" aria-label="Điều hướng di động">
                <a href="{{ route('home') }}" class="nav-link text-dark py-2 border-bottom">Trang chủ</a>
                <a href="{{ route('jobs.index') }}" class="nav-link text-dark py-2 border-bottom">Việc làm</a>
                <a href="{{ route('companies.index') }}" class="nav-link text-dark py-2 border-bottom">Công ty</a>
                <a href="{{ route('contact.show') }}" class="nav-link text-dark py-2 border-bottom">Liên hệ</a>
                <a href="{{ route('jobs.index') }}" class="btn btn-primary fw-semibold mt-3" style="min-height:48px">
                    Tìm việc ngay
                </a>
            </nav>
        </div>
    </div>

    <main class="py-4 flex-grow-1">
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

    <footer class="pub-footer py-5 mt-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-12 col-md-4">
                    <p class="fw-bold fs-5 mb-2">{{ config('app.name', 'vieclam88') }}</p>
                    <p class="mb-0">Kết nối người lao động với việc làm khu công nghiệp đang tuyển.</p>
                </div>
                <div class="col-6 col-md-4">
                    <p class="fw-semibold mb-2">Khám phá</p>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                        <li><a href="{{ route('jobs.index') }}" class="text-decoration-none">Việc làm</a></li>
                        <li><a href="{{ route('companies.index') }}" class="text-decoration-none">Công ty</a></li>
                        <li><a href="{{ route('pages.about') }}" class="text-decoration-none">Giới thiệu</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-4">
                    <p class="fw-semibold mb-2">Hỗ trợ</p>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                        <li><a href="{{ route('contact.show') }}" class="text-decoration-none">Liên hệ</a></li>
                        <li><a href="{{ route('faqs.index') }}" class="text-decoration-none">Câu hỏi thường gặp</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <p class="small mb-0">&copy; {{ date('Y') }} {{ config('app.name', 'vieclam88') }}.</p>
        </div>
    </footer>
</body>
</html>
