<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use App\Models\Ward;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Branch $branch */
        $branch = $this->route('branch');

        return $this->user()->can('update', $branch);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Branch $branch */
        $branch = $this->route('branch');

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($branch->id)],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'zalo' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            // TASK 1.3: xem StoreBranchRequest — form mới chỉ ghi ward_id.
            'ward_id' => [
                'required',
                Rule::exists(Ward::class, 'id')->where('is_active', true),
            ],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
