<?php

namespace App\Http\Requests\Hr\Application;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Application $application */
        $application = $this->route('application');

        return $this->user()->can('scheduleAppointment', $application);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:callback,interview'],
            // application_appointments.scheduled_at la cot NOT NULL — bat buoc voi ca 2 type
            // (docs/CORE-FLOWS.md muc 5.3: "Hen goi lai bat buoc co scheduled_at").
            'scheduled_at' => ['required', 'date'],
            'location_detail' => ['nullable', 'string', 'max:255'],
        ];
    }
}
