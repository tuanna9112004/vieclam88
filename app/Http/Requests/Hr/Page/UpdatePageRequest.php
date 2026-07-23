<?php

namespace App\Http\Requests\Hr\Page;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Page $page */
        $page = $this->route('page');

        return $this->user()->can('update', $page);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Page $page */
        $page = $this->route('page');

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:220',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique('pages', 'slug')->ignore($page->id),
            ],
            'content' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'status' => ['required', 'string', Rule::enum(PageStatus::class)],
        ];
    }
}
