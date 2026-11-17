<?php

namespace App\Http\Requests\Admin;

use App\Support\SiteLocales;
use App\Support\UploadedLogos;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', Rule::in(SiteLocales::codes())],
            'font_stylesheet_url' => ['nullable', 'string', 'max:500', 'url', 'starts_with:https://'],
            'logo' => UploadedLogos::RULES,
            'remove_logo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please give the site a name.',
            'font_stylesheet_url.starts_with' => 'The font stylesheet address must use https.',
            'font_stylesheet_url.url' => 'The font stylesheet address must be a web address, starting with https://.',
            ...UploadedLogos::MESSAGES,
        ];
    }
}
