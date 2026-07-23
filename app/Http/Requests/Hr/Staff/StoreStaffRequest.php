<?php

namespace App\Http\Requests\Hr\Staff;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $branchRules = [
            'required',
            Rule::exists(Branch::class, 'id')->where('status', 'active')->withoutTrashed(),
        ];

        if ($this->user()->isBranchAdmin()) {
            $branchRules[] = Rule::in([$this->user()->branch_id]);
        }

        $allowedRoles = $this->user()->isSuperAdmin()
            ? ['staff', 'branch_admin']
            : ['staff'];

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['sometimes', 'string', Rule::in($allowedRoles)],
            'branch_id' => $branchRules,
        ];
    }
}
