<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Hồ sơ ứng tuyển — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h4 mb-0">Hồ sơ ứng tuyển</h1>
            </div>

            <form method="GET" action="{{ route('hr.applications.index') }}" class="row g-2 mb-4">
                <div class="col-md-3">
                    <input type="text" name="q" class="form-control" placeholder="Tên hoặc SĐT ứng viên" value="{{ $filters['q'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <select name="stage" class="form-select">
                        <option value="">-- Giai đoạn --</option>
                        @foreach ([
                            'new' => 'Mới', 'contacting' => 'Đang liên hệ', 'consulted' => 'Đã tư vấn',
                            'interview_scheduled' => 'Đã hẹn phỏng vấn', 'interviewed' => 'Đã phỏng vấn',
                            'waiting_start' => 'Chờ đi làm', 'started' => 'Đã đi làm', 'closed' => 'Đã đóng',
                        ] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['stage'] ?? null) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($branches->isNotEmpty())
                    <div class="col-md-3">
                        <select name="owner_branch_id[]" class="form-select" multiple>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ in_array($branch->id, $filters['owner_branch_id'] ?? [], true) ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-12 d-flex flex-wrap gap-3 align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="uncontacted" value="1" id="uncontacted" {{ ($filters['uncontacted'] ?? null) ? 'checked' : '' }}>
                        <label class="form-check-label" for="uncontacted">Chưa liên hệ</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="has_callback" value="1" id="has_callback" {{ ($filters['has_callback'] ?? null) ? 'checked' : '' }}>
                        <label class="form-check-label" for="has_callback">Có lịch gọi lại</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="has_interview" value="1" id="has_interview" {{ ($filters['has_interview'] ?? null) ? 'checked' : '' }}>
                        <label class="form-check-label" for="has_interview">Có lịch phỏng vấn</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="needs_duplicate_review" value="1" id="needs_duplicate_review" {{ ($filters['needs_duplicate_review'] ?? null) ? 'checked' : '' }}>
                        <label class="form-check-label" for="needs_duplicate_review">Nghi ngờ trùng</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
                    <a href="{{ route('hr.applications.index') }}" class="btn btn-outline-secondary btn-sm">Xóa lọc</a>
                    <a href="{{ route('hr.applications.export', request()->query()) }}" class="btn btn-outline-success btn-sm">Xuất CSV</a>
                </div>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Ứng viên</th>
                        <th>SĐT</th>
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
                                <a href="{{ route('hr.applications.show', $application) }}">
                                    {{ $application->candidate?->full_name ?? $application->submitted_full_name }}
                                </a>
                                @if ($application->needs_duplicate_review)
                                    <span class="badge text-bg-warning">Nghi ngờ trùng</span>
                                @endif
                            </td>
                            <td>{{ $application->submitted_phone }}</td>
                            <td>{{ $application->job?->title }}</td>
                            <td>{{ $application->job?->company?->name }}</td>
                            <td>{{ $application->ownerBranch?->name }}</td>
                            <td>{{ $application->stage }}</td>
                            <td>{{ $application->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary">Không có hồ sơ nào khớp bộ lọc.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $applications->links() }}
        </div>
    </body>
</html>
