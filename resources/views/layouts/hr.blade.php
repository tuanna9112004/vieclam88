<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'HR') — {{ config('app.name', 'vieclam88') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="hr-shell">
    @php
        $isAdmin = auth()->user()?->isAdmin();

        $hrNavGroups = [
            [
                'items' => [
                    ['route' => 'hr.dashboard', 'pattern' => 'hr.dashboard', 'label' => 'Tổng quan', 'icon' => '📊', 'admin_only' => false],
                    ['route' => 'hr.jobs.index', 'pattern' => 'hr.jobs.*', 'label' => 'Việc làm', 'icon' => '💼', 'admin_only' => false],
                    ['route' => 'hr.applications.index', 'pattern' => 'hr.applications.*', 'label' => 'Hồ sơ ứng tuyển', 'icon' => '📋', 'admin_only' => false],
                    ['route' => 'hr.companies.index', 'pattern' => 'hr.companies.*', 'label' => 'Công ty', 'icon' => '🏢', 'admin_only' => false],
                    ['route' => 'hr.duplicate-reviews.index', 'pattern' => 'hr.duplicate-reviews.*', 'label' => 'Nghi ngờ trùng', 'icon' => '⚠️', 'admin_only' => true],
                ],
            ],
            [
                'heading' => 'Quản trị',
                'admin_only' => true,
                'items' => [
                    ['route' => 'hr.staff.index', 'pattern' => 'hr.staff.*', 'label' => 'Nhân viên', 'icon' => '👥', 'admin_only' => true],
                    ['route' => 'hr.branches.index', 'pattern' => 'hr.branches.*', 'label' => 'Cơ sở', 'icon' => '🏬', 'admin_only' => true],
                    ['route' => 'hr.industrial-parks.index', 'pattern' => 'hr.industrial-parks.*', 'label' => 'Khu công nghiệp', 'icon' => '🏭', 'admin_only' => true],
                    ['route' => 'hr.administrative-units.index', 'pattern' => 'hr.administrative-units.*', 'label' => 'Đơn vị hành chính', 'icon' => '🗺️', 'admin_only' => true],
                    ['route' => 'hr.pages.index', 'pattern' => 'hr.pages.*', 'label' => 'Trang tĩnh', 'icon' => '📄', 'admin_only' => true],
                    ['route' => 'hr.faqs.index', 'pattern' => 'hr.faqs.*', 'label' => 'Câu hỏi thường gặp', 'icon' => '❓', 'admin_only' => true],
                    ['route' => 'hr.settings.index', 'pattern' => 'hr.settings.*', 'label' => 'Cấu hình', 'icon' => '⚙️', 'admin_only' => true],
                ],
            ],
        ];
    @endphp

    <div class="hr-shell__sidebar" id="hr-sidebar">
        <div class="hr-sidebar__brand">
            <a href="{{ route('hr.dashboard') }}" class="text-decoration-none d-flex align-items-center gap-2">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name', 'vieclam88') }}" class="hr-sidebar__logo">
                <span class="hr-sidebar__brand-text">HR</span>
            </a>
        </div>

        <nav class="hr-sidebar__nav" aria-label="Điều hướng HR">
            @foreach ($hrNavGroups as $group)
                @if (empty($group['admin_only']) || $isAdmin)
                    @if (! empty($group['heading']))
                        <p class="hr-sidebar__heading">{{ $group['heading'] }}</p>
                    @endif
                    @foreach ($group['items'] as $item)
                        @if (! $item['admin_only'] || $isAdmin)
                            <a
                                href="{{ route($item['route']) }}"
                                class="hr-sidebar__link @if (request()->routeIs($item['pattern'])) is-active @endif"
                                @if (request()->routeIs($item['pattern'])) aria-current="page" @endif
                            >
                                <span class="hr-sidebar__link-icon" aria-hidden="true">{{ $item['icon'] }}</span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </nav>
    </div>

    <div class="hr-shell__backdrop" id="hr-sidebar-backdrop"></div>

    <div class="hr-shell__main">
        <header class="hr-topbar">
            <button
                type="button"
                class="btn btn-outline-secondary hr-topbar__toggle"
                id="hr-sidebar-toggle"
                aria-label="Mở/đóng menu"
                aria-expanded="false"
                aria-controls="hr-sidebar"
            >
                <span aria-hidden="true">&#9776;</span>
            </button>

            <div class="hr-topbar__title">@yield('title', 'HR')</div>

            <div class="hr-topbar__user">
                <span class="d-none d-sm-inline">
                    {{ auth()->user()->name }}
                    <span class="text-secondary">
                        ({{ $isAdmin ? 'Admin' : 'Staff — '.(auth()->user()->branch?->name ?? 'Cơ sở') }})
                    </span>
                </span>
                <a href="{{ route('hr.password.change') }}" class="btn btn-sm btn-outline-secondary">Đổi mật khẩu</a>
                <form method="POST" action="{{ route('hr.logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">Đăng xuất</button>
                </form>
            </div>
        </header>

        <main class="hr-content">
            @if (session('status'))
                <div class="alert alert-success" role="alert">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        (function () {
            var toggle = document.getElementById('hr-sidebar-toggle');
            var sidebar = document.getElementById('hr-sidebar');
            var backdrop = document.getElementById('hr-sidebar-backdrop');

            function closeSidebar() {
                sidebar.classList.remove('is-open');
                backdrop.classList.remove('is-visible');
                toggle.setAttribute('aria-expanded', 'false');
            }

            function toggleSidebar() {
                var open = sidebar.classList.toggle('is-open');
                backdrop.classList.toggle('is-visible', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            toggle.addEventListener('click', toggleSidebar);
            backdrop.addEventListener('click', closeSidebar);
        })();
    </script>
</body>
</html>
