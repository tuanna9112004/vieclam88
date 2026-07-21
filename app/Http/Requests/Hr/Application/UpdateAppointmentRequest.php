<?php

namespace App\Http\Requests\Hr\Application;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Application $application */
        $application = $this->route('application');

        return $this->user()->can('updateAppointment', $application);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ApplicationAppointment $appointment */
        $appointment = $this->route('appointment');

        return [
            // Chi cap nhat status/outcome (docs/ROUTE-MAP.md) — khong nhan scheduled_at o day,
            // doi lich la tao ban ghi moi qua hr.applications.appointments.store.
            'status' => ['required', 'string', 'in:completed,cancelled,no_show'],
            // Phong van hoan thanh bat buoc co outcome (docs/CORE-FLOWS.md muc 5.3).
            'outcome' => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => $appointment->type === 'interview' && $this->input('status') === 'completed'),
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
