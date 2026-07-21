<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ $application->candidate?->full_name ?? $application->submitted_full_name }} — Hồ sơ ứng tuyển — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5">
            <a href="{{ route('hr.applications.index') }}" class="d-inline-block mb-3 text-decoration-none">&larr; Danh sách hồ sơ</a>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h1 class="h4 mb-1">{{ $application->candidate?->full_name ?? $application->submitted_full_name }}</h1>
                    <p class="text-secondary mb-0">
                        {{ $application->submitted_phone }} —
                        {{ $application->job?->title }} ({{ $application->job?->company?->name }}) —
                        {{ $application->ownerBranch?->name }}
                    </p>
                </div>
                <span class="badge text-bg-secondary">{{ $application->stage }}</span>
            </div>

            <h2 class="h5 mb-3">Lịch sử xử lý</h2>

            @php $currentCycle = null; @endphp
            <ul class="list-unstyled">
                @forelse ($timeline as $entry)
                    @if ($entry['workflow_cycle'] !== null && $entry['workflow_cycle'] !== $currentCycle)
                        @php $currentCycle = $entry['workflow_cycle']; @endphp
                        <li class="text-uppercase small text-secondary fw-semibold mt-3 mb-1">
                            Chu kỳ xử lý #{{ $currentCycle }}
                        </li>
                    @endif
                    <li class="border-start ps-3 pb-3" style="border-color: var(--bs-border-color) !important;">
                        <div class="small text-secondary">
                            {{ $entry['occurred_at']?->format('d/m/Y H:i') }}
                            @if ($entry['actor'])
                                — {{ $entry['actor']->name }}
                            @endif
                        </div>

                        @switch($entry['type'])
                            @case('status_change')
                                <div>
                                    Chuyển giai đoạn:
                                    <strong>{{ $entry['model']->from_stage ?? '(mới)' }} → {{ $entry['model']->to_stage }}</strong>
                                    @if ($entry['model']->close_reason)
                                        — lý do: {{ $entry['model']->close_reason }}
                                    @endif
                                    @if ($entry['model']->note)
                                        <div class="text-secondary">{{ $entry['model']->note }}</div>
                                    @endif
                                </div>
                                @break
                            @case('contact_attempt')
                                <div>
                                    Liên hệ ({{ $entry['model']->channel }}) — kết quả: <strong>{{ $entry['model']->result }}</strong>
                                    @if ($entry['model']->note)
                                        <div class="text-secondary">{{ $entry['model']->note }}</div>
                                    @endif
                                </div>
                                @break
                            @case('appointment')
                                <div>
                                    Lịch {{ $entry['model']->type === 'interview' ? 'phỏng vấn' : 'gọi lại' }}
                                    lúc {{ $entry['model']->scheduled_at?->format('d/m/Y H:i') }}
                                    — trạng thái: <strong>{{ $entry['model']->status }}</strong>
                                    @if ($entry['model']->outcome)
                                        <div class="text-secondary">{{ $entry['model']->outcome }}</div>
                                    @endif
                                </div>
                                @break
                            @case('note')
                                <div>Ghi chú nội bộ: {{ $entry['model']->content }}</div>
                                @break
                            @case('branch_transfer')
                                <div>
                                    Chuyển cơ sở: {{ $entry['model']->fromBranch?->name ?? '(chưa có)' }} → {{ $entry['model']->toBranch?->name }}
                                    @if ($entry['model']->reason)
                                        — lý do: {{ $entry['model']->reason }}
                                    @endif
                                </div>
                                @break
                        @endswitch
                    </li>
                @empty
                    <li class="text-secondary">Chưa có dữ liệu lịch sử.</li>
                @endforelse
            </ul>
        </div>
    </body>
</html>
