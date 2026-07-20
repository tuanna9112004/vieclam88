<?php

namespace App\Http\Requests\Hr\Staff;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ResetStaffPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $staff */
        $staff = $this->route('staff');

        return $this->user()->can('resetPassword', $staff);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
