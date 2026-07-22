<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Dashboard HR — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-light">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Dashboard HR</h1>
                    <p class="text-muted mb-0">Xin chào, {{ auth()->user()->name }} ({{ auth()->user()->isAdmin() ? 'Admin' : 'Staff — ' . (auth()->user()->branch?->name ?? 'Cơ sở') }})</p>
                </div>
                <div>
                    <a href="{{ route('hr.applications.index') }}" class="btn btn-primary me-2">Hồ sơ ứng tuyển</a>
                    <a href="{{ route('hr.jobs.index') }}" class="btn btn-outline-primary me-2">Việc làm</a>
                    <a href="{{ route('hr.companies.index') }}" class="btn btn-outline-primary me-2">Công ty</a>

                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('hr.duplicate-reviews.index') }}" class="btn btn-outline-warning me-2">Nghi ngờ trùng</a>
                        <a href="{{ route('hr.industrial-parks.index') }}" class="btn btn-outline-secondary me-2">KCN</a>
                        <a href="{{ route('hr.branches.index') }}" class="btn btn-outline-secondary me-2">Cơ sở</a>
                    @endif

                    <form method="POST" action="{{ route('hr.logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">Đăng xuất</button>
                    </form>
                </div>
            </div>

            @if (auth()->user()->isAdmin() && $branches->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route('hr.dashboard') }}" class="row align-items-center g-3">
                            <div class="col-auto">
                                <label for="branch_id" class="col-form-label fw-bold">Lọc theo Cơ sở:</label>
                            </div>
                            <div class="col-auto">
                                <select name="branch_id" id="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">-- Tất cả cơ sở --</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $selectedBranchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($selectedBranchId)
                                <div class="col-auto">
                                    <a href="{{ route('hr.dashboard') }}" class="btn btn-link btn-sm text-decoration-none">Xóa lọc cơ sở</a>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            @endif

            <div class="row g-3">
                <!-- Card 1: Hồ sơ mới hôm nay -->
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 border-primary shadow-sm">
                        <div class="card-body">
                            <div class="text-primary fw-bold text-uppercase small">Hồ sơ mới hôm nay</div>
                            <div class="display-6 my-2 fw-bold">{{ number_format($stats['new_today']) }}</div>
                            <a href="{{ route('hr.applications.index', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">Xem danh sách</a>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Chưa liên hệ -->
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 border-warning shadow-sm">
                        <div class="card-body">
                            <div class="text-warning fw-bold text-uppercase small">Chưa liên hệ</div>
                            <div class="display-6 my-2 fw-bold text-warning">{{ number_format($stats['uncontacted']) }}</div>
                            <a href="{{ route('hr.applications.index', ['uncontacted' => 1]) }}" class="btn btn-sm btn-outline-warning w-100 mt-2">Xem danh sách</a>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Đang xử lý -->
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 border-info shadow-sm">
                        <div class="card-body">
                            <div class="text-info fw-bold text-uppercase small">Đang xử lý</div>
                            <div class="display-6 my-2 fw-bold text-info">{{ number_format($stats['processing']) }}</div>
                            <a href="{{ route('hr.applications.index', ['processing' => 1]) }}" class="btn btn-sm btn-outline-info w-100 mt-2">Xem danh sách</a>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Lịch gọi lại hôm nay -->
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 border-secondary shadow-sm">
                        <div class="card-body">
                            <div class="text-secondary fw-bold text-uppercase small">Lịch gọi lại hôm nay</div>
                            <div class="display-6 my-2 fw-bold">{{ number_format($stats['callbacks_today']) }}</div>
                            <a href="{{ route('hr.applications.index', ['callback_today' => 1]) }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">Xem danh sách</a>
                        </div>
                    </div>
                </div>

                <!-- Card 5: Lịch phỏng vấn hôm nay -->
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 border-success shadow-sm">
                        <div class="card-body">
                            <div class="text-success fw-bold text-uppercase small">Lịch phỏng vấn hôm nay</div>
                            <div class="display-6 my-2 fw-bold text-success">{{ number_format($stats['interviews_today']) }}</div>
                            <a href="{{ route('hr.applications.index', ['interview_today' => 1]) }}" class="btn btn-sm btn-outline-success w-100 mt-2">Xem danh sách</a>
                        </div>
                    </div>
                </div>

                <!-- Card 6: Chờ đi làm -->
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 border-primary shadow-sm">
                        <div class="card-body">
                            <div class="text-primary fw-bold text-uppercase small">Chờ đi làm</div>
                            <div class="display-6 my-2 fw-bold">{{ number_format($stats['waiting_start']) }}</div>
                            <a href="{{ route('hr.applications.index', ['stage' => 'waiting_start']) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">Xem danh sách</a>
                        </div>
                    </div>
                </div>

                <!-- Card 7: Đã đi làm -->
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 border-success shadow-sm">
                        <div class="card-body">
                            <div class="text-success fw-bold text-uppercase small">Đã đi làm</div>
                            <div class="display-6 my-2 fw-bold text-success">{{ number_format($stats['started']) }}</div>
                            <a href="{{ route('hr.applications.index', ['stage' => 'started']) }}" class="btn btn-sm btn-outline-success w-100 mt-2">Xem danh sách</a>
                        </div>
                    </div>
                </div>

                <!-- Card 8: Đã đóng -->
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 border-dark shadow-sm">
                        <div class="card-body">
                            <div class="text-dark fw-bold text-uppercase small">Đã đóng</div>
                            <div class="display-6 my-2 fw-bold text-muted">{{ number_format($stats['closed']) }}</div>
                            <a href="{{ route('hr.applications.index', ['stage' => 'closed']) }}" class="btn btn-sm btn-outline-dark w-100 mt-2">Xem danh sách</a>
                        </div>
                    </div>
                </div>

                <!-- Card 9: Việc làm cần xác nhận / xử lý -->
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 border-danger shadow-sm">
                        <div class="card-body">
                            <div class="text-danger fw-bold text-uppercase small">Việc làm cần xử lý</div>
                            <div class="display-6 my-2 fw-bold text-danger">{{ number_format($stats['jobs_needing_verification']) }}</div>
                            <a href="{{ route('hr.jobs.index') }}" class="btn btn-sm btn-outline-danger w-100 mt-2">Quản lý việc làm</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
