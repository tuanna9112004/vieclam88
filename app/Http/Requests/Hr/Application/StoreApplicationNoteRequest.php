<?php

namespace App\Http\Requests\Hr\Application;

use App\Models\Application;
use App\Models\ApplicationNote;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Application $application */
        $application = $this->route('application');

        return $this->user()->can('create', [ApplicationNote::class, $application]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // ADR-071 (docs/CORE-FLOWS.md muc 7.3.1): khong CCCD/dinh danh nhay cam ngoai pham vi
            // can thiet — quy tac phong ngua nghiep vu, khong phai rang buoc ky thuat co the
            // validate tu dong o day.
            'content' => ['required', 'string'],
        ];
    }
}
