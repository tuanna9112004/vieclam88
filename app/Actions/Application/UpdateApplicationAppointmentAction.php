<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * hr.applications.appointments.update (docs/CORE-FLOWS.md muc 5.3): danh dau lich hen
 * completed/cancelled/no_show. Chi doi tu status=scheduled (khong sua de mot appointment da
 * ket thuc); khong bao gio sua scheduled_at o day — doi lich la tao ban ghi moi
 * (ScheduleApplicationAppointmentAction). completed_by/completed_at/workflow_cycle luon
 * server-side; ghi nhan ket qua Appointment khong tu dong doi applications.stage (ADR-009,
 * cung nguyen tac voi Contact Log).
 */
class UpdateApplicationAppointmentAction
{
    /**
     * @param  array{status: string, outcome?: ?string, note?: ?string}  $data
     */
    public function handle(Application $application, ApplicationAppointment $appointment, array $data, User $actor): ApplicationAppointment
    {
        return DB::transaction(function () use ($appointment, $data, $actor) {
            /** @var ApplicationAppointment $locked */
            $locked = ApplicationAppointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'scheduled') {
                throw ValidationException::withMessages([
                    'status' => "Chỉ cập nhật được lịch hẹn đang ở trạng thái 'scheduled'.",
                ]);
            }

            $toStatus = $data['status'];

            if ($locked->type === 'interview' && $toStatus === 'completed' && ! ($data['outcome'] ?? null)) {
                throw ValidationException::withMessages([
                    'outcome' => 'Cần kết quả (outcome) khi hoàn thành lịch phỏng vấn.',
                ]);
            }

            $attributes = [
                'status' => $toStatus,
                'outcome' => $data['outcome'] ?? null,
                'note' => $data['note'] ?? $locked->note,
            ];

            if ($toStatus === 'completed') {
                $attributes['completed_by'] = $actor->id;
                $attributes['completed_at'] = now();
            }

            $locked->update($attributes);

            return $locked;
        });
    }
}
