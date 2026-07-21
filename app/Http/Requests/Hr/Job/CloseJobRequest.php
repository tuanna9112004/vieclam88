<?php

namespace App\Http\Requests\Hr\Job;

use App\Enums\JobCloseReason;
use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CloseJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Job $job */
        $job = $this->route('job');

        return $this->user()->can('close', $job);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'close_reason' => ['required', new Enum(JobCloseReason::class)],
        ];
    }
}
