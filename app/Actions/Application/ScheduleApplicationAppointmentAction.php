<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * docs/CORE-FLOWS.md muc 5.3, 5.4: tao lich goi lai/phong van. Neu Application dang co 1 ban ghi
 * cung type, status=scheduled, cung workflow_cycle hien tai — day la "doi lich": chuyen ban ghi
 * cu sang cancelled (khong sua scheduled_at cua no) roi tao ban ghi moi, trong cung transaction +
 * lockForUpdate tren Application de serialize 2 request doi lich dong thoi. created_by/
 * workflow_cycle luon do server tinh, khong doc tu client.
 */
class ScheduleApplicationAppointmentAction
{
    /**
     * @param  array{type: string, scheduled_at: string, location_detail: ?string}  $data
     */
    public function handle(Application $application, array $data, User $actor): ApplicationAppointment
    {
        return DB::transaction(function () use ($application, $data, $actor) {
            /** @var Application $lockedApplication */
            $lockedApplication = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('scheduleAppointment', $lockedApplication);

            ApplicationAppointment::where('application_id', $lockedApplication->id)
                ->where('type', $data['type'])
                ->where('status', 'scheduled')
                ->where('workflow_cycle', $lockedApplication->workflow_cycle)
                ->update(['status' => 'cancelled']);

            return ApplicationAppointment::create([
                'application_id' => $lockedApplication->id,
                'type' => $data['type'],
                'scheduled_at' => $data['scheduled_at'],
                'location_detail' => $data['location_detail'] ?? null,
                'status' => 'scheduled',
                'workflow_cycle' => $lockedApplication->workflow_cycle,
                'created_by' => $actor->id,
            ]);
        });
    }
}
