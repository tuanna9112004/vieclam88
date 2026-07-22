<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>Chi tiết nghi ngờ trùng lặp #{{ $review->id }} — {{ config('app.name', 'vieclam88') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="{{ route('hr.duplicate-reviews.index') }}" class="text-decoration-none">&larr; Trở lại danh sách</a>
                    <h1 class="h4 mb-0 mt-2">Chi tiết nghi ngờ trùng lặp #{{ $review->id }}</h1>
                </div>
                <div>
                    <a href="{{ route('hr.applications.show', $review->application_id) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                        Xem Hồ sơ {{ $review->application?->code }} &nearr;
                    </a>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100 border-primary">
                        <div class="card-header bg-primary text-white font-weight-bold">
                            Ứng Viên Mới Trong Hồ Sơ (Candidate ID: {{ $review->candidate_id }})
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0">
                                <tr><th>Họ tên:</th><td><strong>{{ $review->candidate?->full_name }}</strong></td></tr>
                                <tr><th>Số điện thoại:</th><td>{{ $review->candidate?->phone }}</td></tr>
                                <tr><th>Số CCCD/CMND:</th><td>{{ $review->candidate?->identity_number ?? 'Chưa cập nhật' }}</td></tr>
                                <tr><th>Ngày sinh:</th><td>{{ $review->candidate?->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật' }}</td></tr>
                                <tr><th>Giới tính:</th><td>{{ $review->candidate?->gender ?? '—' }}</td></tr>
                                <tr><th>Trạng thái:</th><td><span class="badge bg-secondary">{{ $review->candidate?->status }}</span></td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 border-warning">
                        <div class="card-header bg-warning text-dark font-weight-bold">
                            Ứng Viên Nghi Ngờ Trùng (Suspected Root ID: {{ $review->suspected_candidate_id }})
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0">
                                <tr><th>Họ tên:</th><td><strong>{{ $review->suspectedCandidate?->full_name }}</strong></td></tr>
                                <tr><th>Số điện thoại:</th><td>{{ $review->suspectedCandidate?->phone }}</td></tr>
                                <tr><th>Số CCCD/CMND:</th><td>{{ $review->suspectedCandidate?->identity_number ?? 'Chưa cập nhật' }}</td></tr>
                                <tr><th>Ngày sinh:</th><td>{{ $review->suspectedCandidate?->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật' }}</td></tr>
                                <tr><th>Giới tính:</th><td>{{ $review->suspectedCandidate?->gender ?? '—' }}</td></tr>
                                <tr><th>Trạng thái:</th><td><span class="badge bg-secondary">{{ $review->suspectedCandidate?->status }}</span></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header font-weight-bold">
                    Thông tin Đánh Giá Nghi Ngờ
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-3 text-muted">Lý do hệ thống cảnh báo:</div>
                        <div class="col-md-9"><span class="badge text-bg-secondary">{{ $review->reason_code?->value ?? $review->reason_code }}</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 text-muted">Trạng thái hiện tại:</div>
                        <div class="col-md-9">
                            @switch($review->status?->value ?? $review->status)
                                @case('pending')
                                    <span class="badge text-bg-warning">Chờ duyệt (Pending)</span>
                                    @break
                                @case('confirmed_same')
                                    <span class="badge text-bg-danger">Xác nhận trùng 1 người (Confirmed Same)</span>
                                    @break
                                @case('confirmed_distinct')
                                    <span class="badge text-bg-success">Xác nhận 2 người khác nhau (Confirmed Distinct)</span>
                                    @break
                                @case('dismissed')
                                    <span class="badge text-bg-light text-dark">Bỏ qua (Dismissed)</span>
                                    @break
                                @default
                                    <span class="badge text-bg-info">{{ $review->status?->value ?? $review->status }}</span>
                            @endswitch
                        </div>
                    </div>
                    @if ($review->reviewed_by)
                        <div class="row mb-2">
                            <div class="col-md-3 text-muted">Người duyệt:</div>
                            <div class="col-md-9">{{ $review->reviewedBy?->name }} vào lúc {{ $review->reviewed_at?->format('d/m/Y H:i') }}</div>
                        </div>
                    @endif
                    @if ($review->review_note)
                        <div class="row">
                            <div class="col-md-3 text-muted">Ghi chú duyệt:</div>
                            <div class="col-md-9">{{ $review->review_note }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white font-weight-bold">
                    Xử lý Đánh Giá Nghi Ngờ Trùng Lặp
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('hr.duplicate-reviews.resolve', $review) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="status" class="form-label">Chọn Trạng thái Xử lý:</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="">-- Chọn kết luận --</option>
                                <option value="confirmed_same" {{ old('status', $review->status?->value) === 'confirmed_same' ? 'selected' : '' }}>
                                    Xác nhận trùng (Confirmed Same — Lưu ý: Hệ thống không tự động Merge)
                                </option>
                                <option value="confirmed_distinct" {{ old('status', $review->status?->value) === 'confirmed_distinct' ? 'selected' : '' }}>
                                    Xác nhận khác nhau (Confirmed Distinct)
                                </option>
                                <option value="dismissed" {{ old('status', $review->status?->value) === 'dismissed' ? 'selected' : '' }}>
                                    Bỏ qua cảnh báo (Dismissed)
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="review_note" class="form-label">Ghi chú giải trình (Review Note):</label>
                            <textarea name="review_note" id="review_note" class="form-control" rows="3" placeholder="Nhập ghi chú xử lý...">{{ old('review_note', $review->review_note) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Lưu kết quả Xử lý</button>
                    </form>
                </div>
            </div>

            @if ($otherReviews->isNotEmpty())
                <div class="card">
                    <div class="card-header font-weight-bold">
                        Các Cảnh Báo Trùng Khác Của Cùng Hồ Sơ ({{ $otherReviews->count() }})
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Suspected Root</th>
                                    <th>Lý do</th>
                                    <th>Trạng thái</th>
                                    <th>Người duyệt</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($otherReviews as $other)
                                    <tr>
                                        <td>#{{ $other->id }}</td>
                                        <td>{{ $other->suspectedCandidate?->full_name }} ({{ $other->suspectedCandidate?->phone }})</td>
                                        <td>{{ $other->reason_code?->value }}</td>
                                        <td>{{ $other->status?->value }}</td>
                                        <td>{{ $other->reviewedBy?->name ?? '—' }}</td>
                                        <td><a href="{{ route('hr.duplicate-reviews.show', $other) }}" class="btn btn-link btn-sm p-0">Xem</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </body>
</html>
