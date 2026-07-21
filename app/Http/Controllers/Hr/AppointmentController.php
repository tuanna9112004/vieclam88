<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Application\ScheduleApplicationAppointmentAction;
use App\Actions\Application\UpdateApplicationAppointmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Application\StoreAppointmentRequest;
use App\Http\Requests\Hr\Application\UpdateAppointmentRequest;
use App\Models\Application;
use App\Models\ApplicationAppointment;
use Illuminate\Http\RedirectResponse;

class AppointmentController extends Controller
{
    public function store(StoreAppointmentRequest $request, Application $application, ScheduleApplicationAppointmentAction $action): RedirectResponse
    {
        $action->handle($application, $request->validated(), $request->user());

        return redirect()->route('hr.applications.index')->with('status', 'Đã đặt lịch.');
    }

    public function update(
        UpdateAppointmentRequest $request,
        Application $application,
        ApplicationAppointment $appointment,
        UpdateApplicationAppointmentAction $action
    ): RedirectResponse {
        abort_unless($appointment->application_id === $application->id, 404);

        $action->handle($application, $appointment, $request->validated(), $request->user());

        return redirect()->route('hr.applications.index')->with('status', 'Đã cập nhật lịch hẹn.');
    }
}
