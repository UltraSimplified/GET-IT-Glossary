<?php

namespace App\Http\Requests\Admin;

use App\Models\Site;
use App\Support\SiteLocales;
use App\Support\UploadedLogos;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SiteRequest extends FormRequest
{
    /** The "I don't get it" pictures: at rest, on hover and after the click. */
    public const array IDGI_PICTURES = ['idgi_rest', 'idgi_hover', 'idgi_thanks'];

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
            ...$this->pictureRules(),
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function pictureRules(): array
    {
        $rules = [];

        foreach (self::IDGI_PICTURES as $field) {
            $rules[$field] = UploadedLogos::RULES;
            $rules['remove_'.$field] = ['boolean'];
        }

        return $rules;
    }

    /**
     * The hover and thanks pictures stand in for the resting one, so they
     * need it: already set and kept, or uploaded in the same save.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $site = Site::query()->orderBy('id')->first();
            $hasRest = $this->hasFile('idgi_rest')
                || ($site?->idgi_rest !== null && ! $this->boolean('remove_idgi_rest'));

            foreach (['idgi_hover', 'idgi_thanks'] as $field) {
                if ($this->hasFile($field) && ! $hasRest) {
                    $validator->errors()->add($field, __('interface.idgi-picture-rest-first'));
                }
            }
        }];
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
            ...$this->pictureMessages(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pictureMessages(): array
    {
        $messages = [];

        foreach (self::IDGI_PICTURES as $field) {
            $messages[$field.'.mimes'] = __('interface.idgi-picture-type');
            $messages[$field.'.mimetypes'] = __('interface.idgi-picture-type');
            $messages[$field.'.max'] = __('interface.idgi-picture-size');
        }

        return $messages;
    }
}
