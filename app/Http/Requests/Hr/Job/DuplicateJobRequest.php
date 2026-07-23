<?php

namespace App\Http\Requests\Hr\Job;

use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;

class DuplicateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Job $job */
        $job = $this->route('job');

        return $this->user()->can('duplicate', $job);
    }

    public function rules(): array
    {
        return [];
    }
}
