@extends('layouts.hr')

@section('title', ($application->candidate?->full_name ?? $application->submitted_full_name).' — Hồ sơ ứng tuyển')

@section('content')
    @php
        $stageLabels = [
            'new' => 'Mới', 'contacting' => 'Đang liên hệ', 'consulted' => 'Đã tư vấn',
            'interview_scheduled' => 'Đã hẹn phỏng vấn', 'interviewed' => 'Đã phỏng vấn',
            'waiting_start' => 'Chờ đi làm', 'started' => 'Đã đi làm', 'closed' => 'Đã đóng',
        ];
        $channelLabels = [
            'phone' => 'Điện thoại', 'zalo' => 'Zalo', 'sms' => 'SMS', 'email' => 'Email', 'other' => 'Khác',
        ];
        $resultLabels = [
            'reached' => 'Đã liên lạc được', 'no_answer' => 'Không nghe máy', 'busy' => 'Máy bận',
            'wrong_number' => 'Sai số', 'consulted' => 'Đã tư vấn', 'callback_requested' => 'Hẹn gọi lại',
            'interview_agreed' => 'Đồng ý phỏng vấn', 'candidate_refused' => 'Ứng viên từ chối',
            'unsuitable' => 'Không phù hợp', 'message_sent' => 'Đã nhắn tin', 'other' => 'Khác',
        ];
        $closeReasonLabels = [
            'unreachable' => 'Không liên lạc được', 'candidate_cancelled' => 'Ứng viên từ chối',
            'employer_cancelled' => 'Nhà tuyển dụng từ chối', 'unsuitable' => 'Không phù hợp',
            'job_closed' => 'Job đã đóng', 'other' => 'Khác',
        ];
        $appointmentStatusLabels = [
            'scheduled' => 'Đang chờ', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy', 'no_show' => 'Không đến',
        ];

        $forwardOptions = \App\Actions\Application\ChangeApplicationStageAction::forwardOptions($application->stage);
        $canClose = \App\Actions\Application\ChangeApplicationStageAction::isClosable($application->stage);
        $scheduledAppointments = $application->appointments->where('status', 'scheduled');
        $activeNotes = $application->notes->whereNull('deleted_at');
    @endphp

    <a href="{{ route('hr.applications.index') }}" class="d-inline-block mb-3 text-decoration-none">&larr; Danh sách hồ sơ</a>

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

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h1 class="h4 mb-1">{{ $application->candidate?->full_name ?? $application->submitted_full_name }}</h1>
            <p class="text-secondary mb-0">
                {{ $application->submitted_phone }} —
                {{ $application->job?->title }} ({{ $application->job?->company?->name }}) —
                {{ $application->ownerBranch?->name }}
            </p>
        </div>
        <span class="badge text-bg-secondary fs-6">{{ $stageLabels[$application->stage] ?? $application->stage }}</span>
    </div>

    <div class="row g-3 mb-4">
        <!-- Chuyển giai đoạn -->
        @can('changeStage', $application)
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white fw-bold">Chuyển giai đoạn</div>
                    <div class="card-body">
                        @forelse ($forwardOptions as $nextStage)
                            <form method="POST" action="{{ route('hr.applications.stage', $application) }}" class="mb-2">
                                @csrf
                                <input type="hidden" name="to_stage" value="{{ $nextStage }}">

                                @if ($nextStage === 'waiting_start')
                                    <div class="mb-2">
                                        <label class="form-label small">Ngày dự kiến đi làm</label>
                                        <input type="date" name="expected_start_at" class="form-control form-control-sm @error('expected_start_at') is-invalid @enderror" required>
                                        @error('expected_start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                @endif

                                @if ($nextStage === 'started')
                                    <div class="mb-2">
                                        <label class="form-label small">Thời điểm đã đi làm</label>
                                        <input type="datetime-local" name="started_at" class="form-control form-control-sm @error('started_at') is-invalid @enderror" required>
                                        @error('started_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                @endif

                                <button type="submit" class="btn btn-primary btn-sm w-100" style="min-height:44px">
                                    Chuyển sang: {{ $stageLabels[$nextStage] ?? $nextStage }}
                                </button>
                            </form>
                        @empty
                            <p class="text-secondary small mb-0">Không có giai đoạn tiến tiếp theo.</p>
                        @endforelse

                        @if ($canClose)
                            <hr>
                            <form method="POST" action="{{ route('hr.applications.stage', $application) }}">
                                @csrf
                                <input type="hidden" name="to_stage" value="closed">
                                <div class="mb-2">
                                    <label class="form-label small">Lý do đóng hồ sơ</label>
                                    <select name="close_reason" class="form-select form-select-sm @error('close_reason') is-invalid @enderror" required>
                                        <option value="">-- Chọn lý do --</option>
                                        @foreach ($closeReasonLabels as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('close_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100" style="min-height:44px" onclick="return confirm('Xác nhận đóng hồ sơ này?')">Đóng hồ sơ</button>
                            </form>
                        @endif

                        @if ($application->stage === 'closed')
                            <hr>
                            <form method="POST" action="{{ route('hr.applications.stage', $application) }}">
                                @csrf
                                <input type="hidden" name="to_stage" value="new">
                                <div class="mb-2">
                                    <label class="form-label small">Lý do mở lại hồ sơ</label>
                                    <textarea name="note" class="form-control form-control-sm @error('note') is-invalid @enderror" rows="2" required>{{ old('note') }}</textarea>
                                    @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <button type="submit" class="btn btn-outline-success btn-sm w-100" style="min-height:44px">Mở lại hồ sơ</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endcan

        <!-- Ghi nhận liên hệ -->
        @can('recordContact', $application)
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white fw-bold">Ghi nhận liên hệ</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('hr.applications.contacts.store', $application) }}">
                            @csrf
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small">Kênh liên hệ</label>
                                    <select name="channel" class="form-select form-select-sm @error('channel') is-invalid @enderror" required>
                                        @foreach ($channelLabels as $value => $label)
                                            <option value="{{ $value }}" {{ old('channel') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('channel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Kết quả</label>
                                    <select name="result" class="form-select form-select-sm @error('result') is-invalid @enderror" required>
                                        @foreach ($resultLabels as $value => $label)
                                            <option value="{{ $value }}" {{ old('result') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('result')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Ghi chú</label>
                                <textarea name="note" class="form-control form-control-sm @error('note') is-invalid @enderror" rows="2" maxlength="255">{{ old('note') }}</textarea>
                                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100" style="min-height:44px">Lưu liên hệ</button>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <!-- Lịch hẹn -->
        @can('scheduleAppointment', $application)
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white fw-bold">Lịch hẹn</div>
                    <div class="card-body">
                        @forelse ($scheduledAppointments as $appointment)
                            <div class="border rounded p-2 mb-2">
                                <div class="small fw-bold">
                                    {{ $appointment->type === 'interview' ? 'Phỏng vấn' : 'Gọi lại' }}
                                    — {{ $appointment->scheduled_at?->format('d/m/Y H:i') }}
                                </div>
                                @if ($appointment->location_detail)
                                    <div class="small text-secondary">{{ $appointment->location_detail }}</div>
                                @endif

                                @can('updateAppointment', $application)
                                    <form method="POST" action="{{ route('hr.applications.appointments.update', [$application, $appointment]) }}" class="mt-2">
                                        @csrf
                                        @method('PUT')
                                        <div class="mb-2">
                                            <label class="form-label small">Kết quả {{ $appointment->type === 'interview' ? '(bắt buộc khi hoàn thành)' : '' }}</label>
                                            <input type="text" name="outcome" class="form-control form-control-sm @error('outcome') is-invalid @enderror" maxlength="255">
                                            @error('outcome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button type="submit" name="status" value="completed" class="btn btn-sm btn-outline-success" style="min-height:44px">Hoàn thành</button>
                                            <button type="submit" name="status" value="no_show" class="btn btn-sm btn-outline-warning" style="min-height:44px">Không đến</button>
                                            <button type="submit" name="status" value="cancelled" class="btn btn-sm btn-outline-danger" style="min-height:44px">Hủy</button>
                                        </div>
                                    </form>
                                @endcan
                            </div>
                        @empty
                            <p class="text-secondary small">Chưa có lịch hẹn nào đang chờ.</p>
                        @endforelse

                        <hr>
                        <form method="POST" action="{{ route('hr.applications.appointments.store', $application) }}">
                            @csrf
                            <p class="small text-secondary">Đặt lịch mới sẽ tự động hủy lịch cùng loại đang chờ (nếu có).</p>
                            <div class="mb-2">
                                <label class="form-label small">Loại lịch hẹn</label>
                                <select name="type" class="form-select form-select-sm @error('type') is-invalid @enderror" required>
                                    <option value="callback" {{ old('type') === 'callback' ? 'selected' : '' }}>Gọi lại</option>
                                    <option value="interview" {{ old('type') === 'interview' ? 'selected' : '' }}>Phỏng vấn</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Thời gian</label>
                                <input type="datetime-local" name="scheduled_at" class="form-control form-control-sm @error('scheduled_at') is-invalid @enderror" required>
                                @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Địa điểm / ghi chú</label>
                                <input type="text" name="location_detail" class="form-control form-control-sm @error('location_detail') is-invalid @enderror" maxlength="255">
                                @error('location_detail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100" style="min-height:44px">Đặt lịch</button>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Ghi chú nội bộ -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white fw-bold">Ghi chú nội bộ</div>
                <div class="card-body">
                    @forelse ($activeNotes as $note)
                        <div class="border rounded p-2 mb-2">
                            <div class="small">{{ $note->content }}</div>
                            <div class="small text-secondary">
                                {{ $note->user?->name }} — {{ $note->created_at?->format('d/m/Y H:i') }}
                                @if ($note->edited_at) (đã sửa) @endif
                            </div>

                            @canany(['update', 'delete'], $note)
                                <div class="d-flex gap-2 mt-1">
                                    @can('update', $note)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit-note-{{ $note->id }}">Sửa</button>
                                    @endcan
                                    @can('delete', $note)
                                        <form method="POST" action="{{ route('hr.applications.notes.destroy', [$application, $note]) }}" onsubmit="return confirm('Xác nhận xóa ghi chú này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                        </form>
                                    @endcan
                                </div>
                            @endcanany

                            @can('update', $note)
                                <div class="collapse mt-2" id="edit-note-{{ $note->id }}">
                                    <form method="POST" action="{{ route('hr.applications.notes.update', [$application, $note]) }}">
                                        @csrf
                                        @method('PUT')
                                        <textarea name="content" class="form-control form-control-sm mb-2" rows="2" required>{{ $note->content }}</textarea>
                                        <button type="submit" class="btn btn-sm btn-primary" style="min-height:44px">Lưu</button>
                                    </form>
                                </div>
                            @endcan
                        </div>
                    @empty
                        <p class="text-secondary small">Chưa có ghi chú nào.</p>
                    @endforelse

                    @can('create', [\App\Models\ApplicationNote::class, $application])
                        <hr>
                        <form method="POST" action="{{ route('hr.applications.notes.store', $application) }}">
                            @csrf
                            <div class="mb-2">
                                <textarea name="content" class="form-control form-control-sm @error('content') is-invalid @enderror" rows="2" placeholder="Thêm ghi chú nội bộ..." required>{{ old('content') }}</textarea>
                                @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100" style="min-height:44px">Thêm ghi chú</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Chuyển cơ sở phụ trách (chỉ Admin) -->
    @can('transferBranch', $application)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Chuyển cơ sở phụ trách</div>
            <div class="card-body">
                <form method="POST" action="{{ route('hr.applications.transfer-branch', $application) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label small">Cơ sở đích</label>
                        <select name="to_branch_id" class="form-select form-select-sm @error('to_branch_id') is-invalid @enderror" required>
                            <option value="">-- Chọn cơ sở --</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('to_branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">Lý do</label>
                        <input type="text" name="reason" class="form-control form-control-sm @error('reason') is-invalid @enderror" maxlength="255" required>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100" style="min-height:44px">Chuyển cơ sở</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

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
                            <strong>{{ $stageLabels[$entry['model']->from_stage] ?? $entry['model']->from_stage ?? '(mới)' }} → {{ $stageLabels[$entry['model']->to_stage] ?? $entry['model']->to_stage }}</strong>
                            @if ($entry['model']->close_reason)
                                — lý do: {{ $closeReasonLabels[$entry['model']->close_reason] ?? $entry['model']->close_reason }}
                            @endif
                            @if ($entry['model']->note)
                                <div class="text-secondary">{{ $entry['model']->note }}</div>
                            @endif
                        </div>
                        @break
                    @case('contact_attempt')
                        <div>
                            Liên hệ ({{ $channelLabels[$entry['model']->channel] ?? $entry['model']->channel }}) —
                            kết quả: <strong>{{ $resultLabels[$entry['model']->result] ?? $entry['model']->result }}</strong>
                            @if ($entry['model']->note)
                                <div class="text-secondary">{{ $entry['model']->note }}</div>
                            @endif
                        </div>
                        @break
                    @case('appointment')
                        <div>
                            Lịch {{ $entry['model']->type === 'interview' ? 'phỏng vấn' : 'gọi lại' }}
                            lúc {{ $entry['model']->scheduled_at?->format('d/m/Y H:i') }}
                            — trạng thái: <strong>{{ $appointmentStatusLabels[$entry['model']->status] ?? $entry['model']->status }}</strong>
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
@endsection
