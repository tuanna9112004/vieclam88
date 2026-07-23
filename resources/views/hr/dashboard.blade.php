@extends('layouts.hr')

@section('title', 'Dashboard HR')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Dashboard HR</h1>
            <p class="text-muted mb-0">Xin chào, {{ auth()->user()->name }} ({{ auth()->user()->isAdmin() ? 'Admin toàn hệ thống' : 'Staff — ' . (auth()->user()->branch?->name ?? 'Cơ sở') }})</p>
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white fw-bold">
                Bộ lọc Dashboard toàn hệ thống
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('hr.dashboard') }}" class="row g-3">
                    <div class="col-md-3">
                        <label for="owner_branch_id" class="form-label small fw-bold">Cơ sở phụ trách:</label>
                        <select name="owner_branch_id[]" id="owner_branch_id" class="form-select form-select-sm">
                            <option value="">-- Tất cả cơ sở --</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ in_array($branch->id, (array) ($filters['owner_branch_id'] ?? [])) ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="company_id" class="form-label small fw-bold">Công ty / Nhà máy:</label>
                        <select name="company_id" id="company_id" class="form-select form-select-sm">
                            <option value="">-- Tất cả công ty --</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" {{ ($filters['company_id'] ?? null) == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="job_id" class="form-label small fw-bold">Việc làm:</label>
                        <select name="job_id" id="job_id" class="form-select form-select-sm">
                            <option value="">-- Tất cả job --</option>
                            @foreach ($jobs as $job)
                                <option value="{{ $job->id }}" {{ ($filters['job_id'] ?? null) == $job->id ? 'selected' : '' }}>
                                    {{ $job->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label small fw-bold">Từ ngày:</label>
                        <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label small fw-bold">Đến ngày:</label>
                        <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary btn-sm me-2">Áp dụng bộ lọc</button>
                        <a href="{{ route('hr.dashboard') }}" class="btn btn-outline-secondary btn-sm">Xóa bộ lọc</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <!-- Card 1: Tổng số hồ sơ -->
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-primary shadow-sm">
                <div class="card-body">
                    <div class="text-primary fw-bold text-uppercase small">Tổng số hồ sơ</div>
                    <div class="display-6 my-2 fw-bold text-primary">{{ number_format($stats['total_applications']) }}</div>
                    <a href="{{ route('hr.applications.index', array_filter($filters ?? [])) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">Xem tất cả</a>
                </div>
            </div>
        </div>

        <!-- Card 2: Tỷ lệ chuyển đổi Application -> Started -->
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-success shadow-sm bg-success bg-opacity-10">
                <div class="card-body">
                    <div class="text-success fw-bold text-uppercase small">Tỷ lệ chuyển đổi (Started)</div>
                    <div class="display-6 my-2 fw-bold text-success">{{ $stats['conversion_rate'] }}%</div>
                    <small class="text-muted">Đã đi làm / Tổng số hồ sơ</small>
                </div>
            </div>
        </div>

        <!-- Card 3: Hồ sơ mới hôm nay -->
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-primary shadow-sm">
                <div class="card-body">
                    <div class="text-primary fw-bold text-uppercase small">Hồ sơ mới hôm nay</div>
                    <div class="display-6 my-2 fw-bold">{{ number_format($stats['new_today']) }}</div>
                    <a href="{{ route('hr.applications.index', array_merge(array_filter($filters ?? []), ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()])) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">Xem danh sách</a>
                </div>
            </div>
        </div>

        <!-- Card 4: Chưa liên hệ -->
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-warning shadow-sm">
                <div class="card-body">
                    <div class="text-warning fw-bold text-uppercase small">Chưa liên hệ</div>
                    <div class="display-6 my-2 fw-bold text-warning">{{ number_format($stats['uncontacted']) }}</div>
                    <a href="{{ route('hr.applications.index', array_merge(array_filter($filters ?? []), ['uncontacted' => 1])) }}" class="btn btn-sm btn-outline-warning w-100 mt-2">Xem danh sách</a>
                </div>
            </div>
        </div>

        <!-- Card 5: Đang xử lý -->
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-info shadow-sm">
                <div class="card-body">
                    <div class="text-info fw-bold text-uppercase small">Đang xử lý</div>
                    <div class="display-6 my-2 fw-bold text-info">{{ number_format($stats['processing']) }}</div>
                    <a href="{{ route('hr.applications.index', array_merge(array_filter($filters ?? []), ['processing' => 1])) }}" class="btn btn-sm btn-outline-info w-100 mt-2">Xem danh sách</a>
                </div>
            </div>
        </div>

        <!-- Card 6: Lịch gọi lại hôm nay -->
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-secondary shadow-sm">
                <div class="card-body">
                    <div class="text-secondary fw-bold text-uppercase small">Lịch gọi lại hôm nay</div>
                    <div class="display-6 my-2 fw-bold">{{ number_format($stats['callbacks_today']) }}</div>
                    <a href="{{ route('hr.applications.index', array_merge(array_filter($filters ?? []), ['callback_today' => 1])) }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">Xem danh sách</a>
                </div>
            </div>
        </div>

        <!-- Card 7: Lịch phỏng vấn hôm nay -->
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-success shadow-sm">
                <div class="card-body">
                    <div class="text-success fw-bold text-uppercase small">Lịch phỏng vấn hôm nay</div>
                    <div class="display-6 my-2 fw-bold text-success">{{ number_format($stats['interviews_today']) }}</div>
                    <a href="{{ route('hr.applications.index', array_merge(array_filter($filters ?? []), ['interview_today' => 1])) }}" class="btn btn-sm btn-outline-success w-100 mt-2">Xem danh sách</a>
                </div>
            </div>
        </div>

        <!-- Card 8: Chờ đi làm -->
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-primary shadow-sm">
                <div class="card-body">
                    <div class="text-primary fw-bold text-uppercase small">Chờ đi làm</div>
                    <div class="display-6 my-2 fw-bold">{{ number_format($stats['waiting_start']) }}</div>
                    <a href="{{ route('hr.applications.index', array_merge(array_filter($filters ?? []), ['stage' => 'waiting_start'])) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">Xem danh sách</a>
                </div>
            </div>
        </div>

        <!-- Card 9: Đã đi làm -->
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-success shadow-sm">
                <div class="card-body">
                    <div class="text-success fw-bold text-uppercase small">Đã đi làm</div>
                    <div class="display-6 my-2 fw-bold text-success">{{ number_format($stats['started']) }}</div>
                    <a href="{{ route('hr.applications.index', array_merge(array_filter($filters ?? []), ['stage' => 'started'])) }}" class="btn btn-sm btn-outline-success w-100 mt-2">Xem danh sách</a>
                </div>
            </div>
        </div>

        <!-- Card 10: Đã đóng -->
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-dark shadow-sm">
                <div class="card-body">
                    <div class="text-dark fw-bold text-uppercase small">Đã đóng</div>
                    <div class="display-6 my-2 fw-bold text-muted">{{ number_format($stats['closed']) }}</div>
                    <a href="{{ route('hr.applications.index', array_merge(array_filter($filters ?? []), ['stage' => 'closed'])) }}" class="btn btn-sm btn-outline-dark w-100 mt-2">Xem danh sách</a>
                </div>
            </div>
        </div>

        <!-- Card 11: Việc làm cần xác nhận / xử lý -->
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

    @if (auth()->user()->isAdmin() && !empty($stats['top_jobs']))
        <div class="row g-4 mb-4">
            <!-- Top Jobs Breakdown -->
            <div class="col-md-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        Top Việc Làm Có Hồ Sơ Nhiều Nhất
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tên Việc Làm</th>
                                    <th>Công Ty</th>
                                    <th>Cơ Sở</th>
                                    <th class="text-center">Số Hồ Sơ</th>
                                    <th class="text-center">Đã Đi Làm</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($stats['top_jobs'] as $job)
                                    <tr>
                                        <td class="fw-bold">{{ $job->title }}</td>
                                        <td>{{ $job->company?->name }}</td>
                                        <td><span class="badge text-bg-secondary">{{ $job->ownerBranch?->name }}</span></td>
                                        <td class="text-center fw-bold">{{ $job->applications_count }}</td>
                                        <td class="text-center text-success fw-bold">{{ $job->started_applications_count }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Companies Breakdown -->
            <div class="col-md-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        Thống Kê Theo Công Ty / Nhà Máy
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Công Ty</th>
                                    <th class="text-center">Số Job</th>
                                    <th class="text-center">Số Hồ Sơ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($stats['companies_stats'] as $company)
                                    <tr>
                                        <td class="fw-bold">{{ $company->name }}</td>
                                        <td class="text-center">{{ $company->jobs_count }}</td>
                                        <td class="text-center fw-bold text-primary">{{ $company->applications_count }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
