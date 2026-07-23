<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Việc làm — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-light">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4 mb-1">Quản lý Việc làm</h1>
                    <p class="text-muted small mb-0">Danh sách các bài tuyển dụng của cơ sở & công ty khách hàng</p>
                </div>
                <div>
                    <a href="{{ route('hr.dashboard') }}" class="btn btn-outline-secondary me-2">Quay lại Dashboard</a>
                    <a href="{{ route('hr.jobs.create') }}" class="btn btn-primary">Thêm việc làm</a>
                </div>
            </div>

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã</th>
                                <th>Tên vị trí</th>
                                <th>Công ty</th>
                                <th>Cơ sở phụ trách</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($jobs as $job)
                                <tr>
                                    <td class="fw-bold">{{ $job->code }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $job->title }}</div>
                                        @if ($job->last_verified_at)
                                            <small class="text-muted">Xác minh gần nhất: {{ $job->last_verified_at->format('d/m/Y H:i') }}</small>
                                        @elseif ($job->status === 'published')
                                            <small class="text-muted">Xác minh gần nhất: chưa từng — tính từ ngày xuất bản</small>
                                        @endif
                                        @php($verificationLevel = $verificationLevels[$job->id] ?? null)
                                        @if ($verificationLevel === 'critical')
                                            <div><span class="badge bg-danger">Quá hạn xác minh</span></div>
                                        @elseif ($verificationLevel === 'warning')
                                            <div><span class="badge bg-warning text-dark">Cần xác minh lại</span></div>
                                        @endif
                                    </td>
                                    <td>{{ $job->company?->name }}</td>
                                    <td><span class="badge text-bg-secondary">{{ $job->ownerBranch?->name }}</span></td>
                                    <td>
                                        @if ($job->status === 'published')
                                            <span class="badge bg-success">Published</span>
                                        @elseif ($job->status === 'draft')
                                            <span class="badge bg-warning text-dark">Draft</span>
                                        @elseif ($job->status === 'paused')
                                            <span class="badge bg-info text-dark">Paused</span>
                                        @else
                                            <span class="badge bg-secondary">Closed</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                                            <!-- 1. Nút Xác Minh (Verify) -->
                                            @can('verify', $job)
                                                <form method="POST" action="{{ route('hr.jobs.verify', $job) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="result" value="still_open">
                                                    <input type="hidden" name="note" value="Xác minh trực tiếp với nhà máy còn tuyển">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Xác minh nhà máy vẫn còn tuyển">
                                                        Xác nhận
                                                    </button>
                                                </form>
                                            @endcan

                                            <!-- 2. Nút Xuất Bản (Publish) -->
                                            @can('publish', $job)
                                                <form method="POST" action="{{ route('hr.jobs.publish', $job) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary" title="Xuất bản việc làm ra public">
                                                        Xuất bản
                                                    </button>
                                                </form>
                                            @endcan

                                            <!-- 3. Nút Tạm Dừng (Pause) -->
                                            @can('pause', $job)
                                                <form method="POST" action="{{ route('hr.jobs.pause', $job) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Tạm dừng tuyển dụng">
                                                        Tạm dừng
                                                    </button>
                                                </form>
                                            @endcan

                                            <!-- 4. Nút Đóng (Close) -->
                                            @can('close', $job)
                                                <form method="POST" action="{{ route('hr.jobs.close', $job) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn đóng Job này?')">
                                                    @csrf
                                                    <input type="hidden" name="close_reason" value="recruitment_filled">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Đóng việc làm">
                                                        Đóng
                                                    </button>
                                                </form>
                                            @endcan

                                            <!-- 5. Nút Sửa -->
                                            @can('update', $job)
                                                <a href="{{ route('hr.jobs.edit', $job) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Chưa có việc làm nào trong danh sách.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $jobs->links() }}
            </div>
        </div>
    </body>
</html>
