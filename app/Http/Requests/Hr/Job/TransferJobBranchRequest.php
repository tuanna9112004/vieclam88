<?php

namespace App\Http\Requests\Hr\Job;

use App\Models\Branch;
use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferJobBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Job $job */
        $job = $this->route('job');

        return $this->user()->can('transferBranch', $job);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'to_branch_id' => [
                'required',
                Rule::exists(Branch::class, 'id')->where('status', 'active')->withoutTrashed(),
            ],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
