<?php

namespace App\Http\Requests\Admin;

use App\Models\Term;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TermRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'term' => ['required', 'string', 'max:255'],
            'term_ordered' => ['nullable', 'string', 'max:255'],
            'term_status' => ['required', Rule::in(Term::EDITABLE_STATUSES)],
            'do_not_use' => ['boolean'],
            'tags' => ['array'],
            'tags.*' => ['string', Rule::in(config('glossary.tags'))],
            'see_also' => ['nullable', 'string'],
            'synonyms' => ['nullable', 'string', 'max:255'],
            'warning' => ['nullable', 'string'],
            'definition' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'common_variants' => ['nullable', 'string'],
            'audio_clip' => ['nullable', 'string'],
            'video' => ['nullable', 'string'],
            'test' => ['nullable', 'string'],
            'cartoon' => ['nullable', 'string'],
            'example' => ['nullable', 'string'],
            'editors' => ['sometimes', 'array'],
            'editors.*' => ['integer', 'exists:users,id'],
            // "update" overwrites this version; "version" keeps it as history and saves a new one.
            'mode' => ['sometimes', Rule::in(['update', 'version'])],
        ];
    }

    /**
     * The validated input as term attributes.
     *
     * @return array<string, mixed>
     */
    public function termAttributes(): array
    {
        $data = $this->safe()->except(['editors', 'mode', 'tags']);

        return [
            ...$data,
            'term_ordered' => $data['term_ordered'] ?? '',
            'do_not_use' => (bool) ($data['do_not_use'] ?? false),
            'tags' => implode(',', $this->validated('tags', [])),
            // Only live terms appear on the public glossary.
            'enabled' => $data['term_status'] === Term::STATUS_LIVE,
            'updated_by' => $this->user()->id,
        ];
    }
}
