<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\ApplicationContactAttempt;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Transition matrix chinh thuc (docs/CORE-FLOWS.md muc 5.1), phan "tien" (khong tinh dong/mo
 * lai). `to_stage=closed` delegate sang CloseApplicationAction; `to_stage=new` (chi hop le khi
 * dang closed) delegate sang ReopenApplicationAction (muc 5.5) — ca 2 dung chung route
 * hr.applications.stage theo docs/ROUTE-MAP.md. `started` la trang thai cuoi (khong co key trong
 * TRANSITIONS).
 *
 * Moi dieu kien "Co Contact Log/Appointment ..." bat buoc thuoc dung workflow_cycle hien tai cua
 * Application tai thoi diem kiem tra (muc 5.4) — du lieu chu ky truoc khong duoc dung lam bang
 * chung mo khoa, du van con hien thi day du trong lich su.
 */
class ChangeApplicationStageAction
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'new' => ['contacting'],
        'contacting' => ['consulted'],
        'consulted' => ['interview_scheduled'],
        'interview_scheduled' => ['interviewed'],
        'interviewed' => ['waiting_start'],
        'waiting_start' => ['started'],
    ];

    public function __construct(
        private readonly CloseApplicationAction $closeAction,
        private readonly ReopenApplicationAction $reopenAction,
    ) {
    }

    /**
     * @param  array{to_stage: string, close_reason?: ?string, expected_start_at?: ?string, started_at?: ?string, note?: ?string}  $data
     */
    public function handle(Application $application, array $data, User $actor): Application
    {
        $toStage = $data['to_stage'];

        if ($toStage === 'closed') {
            return $this->closeAction->handle($application, $data['close_reason'] ?? '', $actor);
        }

        if ($toStage === 'new') {
            return $this->reopenAction->handle($application, $data['note'] ?? '', $actor);
        }

        return DB::transaction(function () use ($application, $data, $toStage, $actor) {
            /** @var Application $locked */
            $locked = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();

            $fromStage = $locked->stage;

            $this->assertValidTransition($fromStage, $toStage);
            $this->assertEvidence($locked, $fromStage, $toStage);

            $attributes = [
                'stage' => $toStage,
                'stage_changed_at' => now(),
            ];

            if ($toStage === 'waiting_start') {
                if (! ($data['expected_start_at'] ?? null)) {
                    throw ValidationException::withMessages([
                        'expected_start_at' => 'Cần ngày dự kiến đi làm.',
                    ]);
                }
                $attributes['expected_start_at'] = $data['expected_start_at'];
            }

            if ($toStage === 'started') {
                if (! ($data['started_at'] ?? null)) {
                    throw ValidationException::withMessages([
                        'started_at' => 'Cần thời điểm đã đi làm.',
                    ]);
                }
                $attributes['started_at'] = $data['started_at'];
            }

            $locked->update($attributes);

            ApplicationStatusHistory::create([
                'application_id' => $locked->id,
                'from_stage' => $fromStage,
                'to_stage' => $toStage,
                'workflow_cycle' => $locked->workflow_cycle,
                'changed_by' => $actor->id,
                'actor_type' => 'user',
            ]);

            return $locked;
        });
    }

    private function assertValidTransition(string $fromStage, string $toStage): void
    {
        if (! in_array($toStage, self::TRANSITIONS[$fromStage] ?? [], true)) {
            throw ValidationException::withMessages([
                'to_stage' => "Không thể chuyển từ '{$fromStage}' sang '{$toStage}'.",
            ]);
        }
    }

    private function assertEvidence(Application $application, string $fromStage, string $toStage): void
    {
        $applicationId = $application->id;
        $cycle = $application->workflow_cycle;

        $missingMessage = match ("{$fromStage}->{$toStage}") {
            'new->contacting' => $this->hasContactAttempt($applicationId, $cycle)
                ? null : 'Cần ít nhất 1 lần liên hệ (Contact Log) trong chu kỳ hiện tại.',
            'contacting->consulted' => $this->hasConsultedResult($applicationId, $cycle)
                ? null : 'Cần Contact Log có kết quả "Đã tư vấn" hoặc "Đồng ý phỏng vấn" trong chu kỳ hiện tại.',
            'consulted->interview_scheduled' => $this->hasScheduledInterview($applicationId, $cycle)
                ? null : 'Cần lịch phỏng vấn (đang chờ) trong chu kỳ hiện tại.',
            'interview_scheduled->interviewed', 'interviewed->waiting_start' => $this->hasCompletedInterview($applicationId, $cycle)
                ? null : 'Cần lịch phỏng vấn đã hoàn thành trong chu kỳ hiện tại.',
            default => null,
        };

        if ($missingMessage !== null) {
            throw ValidationException::withMessages(['to_stage' => $missingMessage]);
        }
    }

    private function hasContactAttempt(int $applicationId, int $cycle): bool
    {
        return ApplicationContactAttempt::where('application_id', $applicationId)
            ->where('workflow_cycle', $cycle)
            ->exists();
    }

    private function hasConsultedResult(int $applicationId, int $cycle): bool
    {
        return ApplicationContactAttempt::where('application_id', $applicationId)
            ->where('workflow_cycle', $cycle)
            ->whereIn('result', ['consulted', 'interview_agreed'])
            ->exists();
    }

    private function hasScheduledInterview(int $applicationId, int $cycle): bool
    {
        return ApplicationAppointment::where('application_id', $applicationId)
            ->where('workflow_cycle', $cycle)
            ->where('type', 'interview')
            ->where('status', 'scheduled')
            ->exists();
    }

    private function hasCompletedInterview(int $applicationId, int $cycle): bool
    {
        return ApplicationAppointment::where('application_id', $applicationId)
            ->where('workflow_cycle', $cycle)
            ->where('type', 'interview')
            ->where('status', 'completed')
            ->exists();
    }
}
