<?php

namespace App\Http\Requests\Public;

use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;

class JobIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * industrial_park_id/administrative_unit_id/work_shift_id/salary chấp nhận cả 1 giá trị đơn
     * (form cũ, tương thích ngược) lẫn mảng nhiều giá trị (multi-select mới) — chuẩn hoá về mảng
     * trước validate để rule/controller chỉ cần xử lý 1 dạng.
     */
    protected function prepareForValidation(): void
    {
        foreach (['industrial_park_id', 'administrative_unit_id', 'work_shift_id', 'salary'] as $field) {
            $value = $this->input($field);

            if ($value !== null && $value !== '' && ! is_array($value)) {
                $this->merge([$field => [$value]]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:191'],
            'company_id' => ['nullable', 'integer'],
            'industrial_park_id' => ['nullable', 'array'],
            'industrial_park_id.*' => ['integer'],
            'administrative_unit_id' => ['nullable', 'array'],
            'administrative_unit_id.*' => ['integer'],
            'work_shift_id' => ['nullable', 'array'],
            'work_shift_id.*' => ['integer'],
            'salary' => ['nullable', 'array'],
            'salary.*' => ['string', 'in:thoa-thuan,'.implode(',', array_keys(Job::SALARY_BUCKETS))],
            'shuttle_bus' => ['nullable', 'in:1'],
            'accommodation' => ['nullable', 'in:1'],
            'sort' => ['nullable', 'string', 'in:latest,salary_desc,urgent'],
        ];
    }
}
