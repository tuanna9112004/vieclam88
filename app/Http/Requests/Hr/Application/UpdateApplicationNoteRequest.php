<?php

namespace App\Http\Requests\Hr\Application;

use App\Models\ApplicationNote;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ApplicationNote $note */
        $note = $this->route('note');

        return $this->user()->can('update', $note);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
        ];
    }
}
