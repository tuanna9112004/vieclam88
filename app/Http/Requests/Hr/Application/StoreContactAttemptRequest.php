<?php

namespace App\Http\Requests\Hr\Application;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Application $application */
        $application = $this->route('application');

        return $this->user()->can('recordContact', $application);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'in:phone,zalo,sms,email,other'],
            'result' => ['required', 'string', 'in:reached,no_answer,busy,wrong_number,consulted,callback_requested,interview_agreed,candidate_refused,unsuitable,message_sent,other'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
