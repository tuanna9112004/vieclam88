<?php

namespace App\Http\Requests\Hr\Staff;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $staff */
        $staff = $this->route('staff');

        return $this->user()->can('update', $staff);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $staff */
        $staff = $this->route('staff');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($staff->id)],
            'branch_id' => ['required', Rule::exists(Branch::class, 'id')->where('status', 'active')->withoutTrashed()],
        ];
    }
}
