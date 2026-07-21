<?php

namespace App\Http\Requests\Hr\Job;

use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;

class PublishJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Job $job */
        $job = $this->route('job');

        return $this->user()->can('publish', $job);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'verification_override_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
