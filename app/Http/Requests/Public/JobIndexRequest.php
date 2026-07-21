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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:191'],
            'company_id' => ['nullable', 'integer'],
            'industrial_park_id' => ['nullable', 'integer'],
            'administrative_unit_id' => ['nullable', 'integer'],
            'work_shift_id' => ['nullable', 'integer'],
            'salary' => ['nullable', 'string', 'in:thoa-thuan,'.implode(',', array_keys(Job::SALARY_BUCKETS))],
            'shuttle_bus' => ['nullable', 'in:1'],
            'accommodation' => ['nullable', 'in:1'],
            'sort' => ['nullable', 'string', 'in:latest,salary_desc,urgent'],
        ];
    }
}
