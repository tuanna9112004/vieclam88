<?php

namespace App\Http\Requests\Hr\Application;

use App\Models\Application;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferApplicationBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Application $application */
        $application = $this->route('application');

        return $this->user()->can('transferBranch', $application);
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
