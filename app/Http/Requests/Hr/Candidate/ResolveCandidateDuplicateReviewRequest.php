<?php

namespace App\Http\Requests\Hr\Candidate;

use App\Enums\CandidateDuplicateReviewStatus;
use App\Models\CandidateDuplicateReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveCandidateDuplicateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CandidateDuplicateReview $duplicateReview */
        $duplicateReview = $this->route('duplicateReview');

        return $this->user()->can('resolve', $duplicateReview);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(CandidateDuplicateReviewStatus::class),
                Rule::notIn([CandidateDuplicateReviewStatus::Pending->value]),
            ],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
