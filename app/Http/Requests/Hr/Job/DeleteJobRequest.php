<?php

namespace App\Http\Requests\Hr\Job;

use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;

class DeleteJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Job $job */
        $job = $this->route('job');

        return $this->user()->can('delete', $job);
    }

    public function rules(): array
    {
        return [];
    }
}
