@extends('layouts.hr')

@section('title', $candidate->full_name.' — Ứng viên')

@section('content')
    @php
        $genderLabels = ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'];
        $stageLabels = [
            'new' => 'Mới', 'contacting' => 'Đang liên hệ', 'consulted' => 'Đã tư vấn',
            'interview_scheduled' => 'Đã hẹn phỏng vấn', 'interviewed' => 'Đã phỏng vấn',
            'waiting_start' => 'Chờ đi làm', 'started' => 'Đã đi làm', 'closed' => 'Đã đóng',
        ];
        $reasonLabels = [
            'same_phone_missing_dob' => 'Cùng SĐT, thiếu ngày sinh',
            'same_phone_different_name' => 'Cùng SĐT, tên khác nhau',
            'same_identity_conflicting_dob' => 'Cùng SĐT/tên, ngày sinh mâu thuẫn',
            'multiple_exact_matches' => 'Trùng khớp nhiều ứng viên gốc',
            'other' => 'Khác',
        ];
        $isAnonymized = $candidate->status === 'anonymized';
        $isActionable = $candidate->status === 'active';
    @endphp

    <a href="javascript:history.back()" class="d-inline-block mb-3 text-decoration-none">&larr; Quay lại</a>

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

    @if ($isAnonymized)
        <div class="alert alert-secondary" role="alert">
            <strong>Ứng viên đã ẩn danh.</strong> Toàn bộ thông tin định danh (họ tên, SĐT, ngày sinh, địa chỉ) đã bị xóa/che vĩnh viễn và không thể khôi phục.
            @if ($candidate->anonymized_at)
                Thực hiện bởi {{ $candidate->anonymizedBy?->name }} lúc {{ $candidate->anonymized_at->format('d/m/Y H:i') }}.
            @endif
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h1 class="h4 mb-1">{{ $candidate->full_name }}</h1>
            <p class="text-secondary mb-0">Mã ứng viên: {{ $candidate->public_id }}</p>
        </div>
        <span class="badge fs-6 {{ $isAnonymized ? 'text-bg-secondary' : 'text-bg-success' }}">
            {{ $isAnonymized ? 'Đã ẩn danh' : 'Đang hoạt động' }}
        </span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white fw-bold">Thông tin ứng viên</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th class="text-secondary" style="width:40%">Ngày sinh</th><td>{{ $candidate->date_of_birth?->format('d/m/Y') ?? '—' }}</td></tr>
                        <tr><th class="text-secondary">Giới tính</th><td>{{ $genderLabels[$candidate->gender] ?? '—' }}</td></tr>
                        <tr><th class="text-secondary">Học vấn</th><td>{{ $candidate->education_level ?? '—' }}</td></tr>
                        <tr><th class="text-secondary">Kinh nghiệm</th><td>{{ $candidate->experience_summary ?? '—' }}</td></tr>
                        <tr><th class="text-secondary">Nơi ở hiện tại</th><td>{{ $candidate->currentAdministrativeUnit?->name ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white fw-bold">Liên hệ</div>
                <div class="card-body">
                    @forelse ($candidate->contacts as $contact)
                        <div class="mb-1">
                            <span class="badge text-bg-light text-dark">{{ strtoupper($contact->type) }}</span>
                            {{ $contact->value }}
                            @if ($contact->is_primary)<span class="badge text-bg-info">Chính</span>@endif
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">Chưa có thông tin liên hệ.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if ($mergedSources->isNotEmpty())
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Ứng viên đã gộp vào đây ({{ $mergedSources->count() }})</div>
            <div class="card-body">
                @foreach ($mergedSources as $source)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-bold">{{ $source->full_name }}</div>
                        <div class="small text-secondary">
                            Gộp bởi {{ $source->mergedBy?->name }} lúc {{ $source->merged_at?->format('d/m/Y H:i') }}
                            @if ($source->merge_reason) — lý do: {{ $source->merge_reason }} @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Hồ sơ ứng tuyển ({{ $applications->count() }})</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Job</th>
                        <th>Công ty</th>
                        <th>Cơ sở</th>
                        <th>Giai đoạn</th>
                        <th>Ngày nộp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td>
                                <a href="{{ route('hr.applications.show', $application) }}">{{ $application->job?->title }}</a>
                                @if ((int) $application->candidate_id !== (int) $candidate->id)
                                    <div class="small text-secondary">(hồ sơ của {{ $application->candidate?->full_name }})</div>
                                @endif
                            </td>
                            <td>{{ $application->job?->company?->name }}</td>
                            <td><span class="badge text-bg-secondary">{{ $application->ownerBranch?->name }}</span></td>
                            <td>{{ $stageLabels[$application->stage] ?? $application->stage }}</td>
                            <td>{{ $application->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-3">Không có hồ sơ nào trong phạm vi cơ sở của bạn.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (auth()->user()->isAdmin() && $duplicateReviews->isNotEmpty())
        <div class="card shadow-sm mb-4 border-warning">
            <div class="card-header bg-warning bg-opacity-25 fw-bold">Nghi ngờ trùng đang chờ xử lý ({{ $duplicateReviews->count() }})</div>
            <div class="card-body">
                @foreach ($duplicateReviews as $review)
                    <div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div>
                                <strong>{{ $review->candidate?->full_name }}</strong>
                                ↔
                                <strong>{{ $review->suspectedCandidate?->full_name }}</strong>
                            </div>
                            <div class="small text-secondary">{{ $reasonLabels[$review->reason_code?->value ?? $review->reason_code] ?? $review->reason_code?->value }}</div>
                        </div>
                        <a href="{{ route('hr.duplicate-reviews.show', $review) }}" class="btn btn-sm btn-outline-warning" style="min-height:44px">Xem xử lý</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @can('merge', $candidate)
        @if ($isActionable)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Gộp ứng viên (Merge)</div>
                <div class="card-body">
                    <p class="small text-secondary">
                        Gộp <strong>{{ $candidate->full_name }}</strong> (ứng viên này) vào một ứng viên khác đã tồn tại trong hệ thống.
                        Sau khi gộp, ứng viên này sẽ chuyển trạng thái "đã gộp" và không thể sửa/gộp lại.
                    </p>
                    <form method="POST" action="{{ route('hr.candidates.merge', $candidate) }}" class="row g-2" onsubmit="return confirm('Xác nhận gộp ứng viên này? Hành động chỉ Admin mới thực hiện được.')">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label small">ID ứng viên đích</label>
                            <input type="number" name="target_candidate_id" class="form-control form-control-sm @error('target_candidate_id') is-invalid @enderror" value="{{ old('target_candidate_id') }}" required>
                            @error('target_candidate_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">Lý do gộp</label>
                            <input type="text" name="reason" class="form-control form-control-sm @error('reason') is-invalid @enderror" maxlength="255" value="{{ old('reason') }}" required>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">ID hồ sơ muốn giữ (nếu trùng Job, để trống nếu để hệ thống tự chọn)</label>
                            <input type="number" name="kept_application_id" class="form-control form-control-sm @error('kept_application_id') is-invalid @enderror" value="{{ old('kept_application_id') }}">
                            @error('kept_application_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100" style="min-height:44px">Gộp</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endcan

    @can('anonymize', $candidate)
        @if ($isActionable)
            <div class="card shadow-sm mb-4 border-danger">
                <div class="card-header bg-danger bg-opacity-10 fw-bold text-danger">Ẩn danh ứng viên (Anonymize)</div>
                <div class="card-body">
                    <p class="small text-danger">
                        <strong>Cảnh báo:</strong> hành động này xóa/che vĩnh viễn họ tên, số điện thoại, ngày sinh, địa chỉ của ứng viên
                        và toàn bộ hồ sơ ứng tuyển liên quan. <strong>Không thể hoàn tác.</strong>
                        Chỉ thực hiện khi ứng viên yêu cầu xóa dữ liệu cá nhân đã được xác minh.
                    </p>
                    <form method="POST" action="{{ route('hr.candidates.anonymize', $candidate) }}" onsubmit="return confirm('XÁC NHẬN CUỐI: ẩn danh ứng viên này KHÔNG THỂ hoàn tác. Tiếp tục?')">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">Nhập chính xác họ tên ứng viên (<strong>{{ $candidate->full_name }}</strong>) để xác nhận</label>
                            <input type="text" name="confirm_name" class="form-control form-control-sm @error('confirm_name') is-invalid @enderror" required>
                            @error('confirm_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm" style="min-height:44px">Ẩn danh vĩnh viễn</button>
                    </form>
                </div>
            </div>
        @endif
    @endcan
@endsection
