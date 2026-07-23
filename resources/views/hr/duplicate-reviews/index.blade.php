@extends('layouts.hr')

@section('title', 'Duyệt hồ sơ nghi ngờ trùng lặp')

@section('content')
    <h1 class="h4 mb-4">Duyệt hồ sơ nghi ngờ trùng lặp (Duplicate Review)</h1>

    <form method="GET" action="{{ route('hr.duplicate-reviews.index') }}" class="row g-2 mb-4">
        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Chờ duyệt (Pending)</option>
                <option value="confirmed_same" {{ $statusFilter === 'confirmed_same' ? 'selected' : '' }}>Đã xác nhận trùng (Confirmed Same)</option>
                <option value="confirmed_distinct" {{ $statusFilter === 'confirmed_distinct' ? 'selected' : '' }}>Đã xác nhận khác nhau (Confirmed Distinct)</option>
                <option value="dismissed" {{ $statusFilter === 'dismissed' ? 'selected' : '' }}>Đã bỏ qua (Dismissed)</option>
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Tất cả trạng thái</option>
            </select>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID Review</th>
                    <th>Mã Hồ sơ</th>
                    <th>Ứng viên Mới</th>
                    <th>Ứng viên Nghi ngờ (Root)</th>
                    <th>Lý do Nghi ngờ</th>
                    <th>Trạng thái</th>
                    <th>Người duyệt</th>
                    <th>Thời gian duyệt</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reviews as $reviewItem)
                    <tr>
                        <td>#{{ $reviewItem->id }}</td>
                        <td>
                            <a href="{{ route('hr.applications.show', $reviewItem->application_id) }}">
                                {{ $reviewItem->application?->code ?? '#'.$reviewItem->application_id }}
                            </a>
                        </td>
                        <td>
                            <strong>{{ $reviewItem->candidate?->full_name }}</strong><br>
                            <small class="text-muted">{{ $reviewItem->candidate?->phone }}</small>
                        </td>
                        <td>
                            <strong>{{ $reviewItem->suspectedCandidate?->full_name }}</strong><br>
                            <small class="text-muted">{{ $reviewItem->suspectedCandidate?->phone }}</small>
                        </td>
                        <td>
                            <span class="badge text-bg-secondary">{{ $reviewItem->reason_code?->value ?? $reviewItem->reason_code }}</span>
                        </td>
                        <td>
                            @switch($reviewItem->status?->value ?? $reviewItem->status)
                                @case('pending')
                                    <span class="badge text-bg-warning">Chờ duyệt</span>
                                    @break
                                @case('confirmed_same')
                                    <span class="badge text-bg-danger">Xác nhận trùng</span>
                                    @break
                                @case('confirmed_distinct')
                                    <span class="badge text-bg-success">Xác nhận khác nhau</span>
                                    @break
                                @case('dismissed')
                                    <span class="badge text-bg-light text-dark">Đã bỏ qua</span>
                                    @break
                                @default
                                    <span class="badge text-bg-info">{{ $reviewItem->status?->value ?? $reviewItem->status }}</span>
                            @endswitch
                        </td>
                        <td>{{ $reviewItem->reviewedBy?->name ?? '—' }}</td>
                        <td>{{ $reviewItem->reviewed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('hr.duplicate-reviews.show', $reviewItem) }}" class="btn btn-primary btn-sm">
                                Xem & Xử lý
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-4">
                            Không có bản ghi nghi ngờ trùng lặp nào.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reviews->links() }}
@endsection
