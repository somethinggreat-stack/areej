<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[\d\s()+-]{9,}$/'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'event_type' => ['nullable', 'string', 'max:64'],
            'event_date' => ['nullable', 'date', 'after_or_equal:today'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'venue' => ['nullable', 'string', 'max:190'],
            'service_style' => ['nullable', 'string', 'max:64'],
            'extras' => ['nullable', 'array'],
            'extras.*' => ['string', 'max:64'],
            'dietary' => ['nullable', 'string', 'max:500'],
            'message' => ['nullable', 'string', 'max:2000'],

            'privacy_consent' => ['accepted'],

            // Honeypot: a real person never fills this in.
            'company_website' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // We need at least one way to reply.
            if (blank($this->input('phone')) && blank($this->input('email'))) {
                $validator->errors()->add('phone', __('Please give us a phone number or an email so we can reply.'));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Please tell us your name.'),
            'phone.regex' => __('That phone number looks incomplete.'),
            'email.email' => __('That email address looks incorrect.'),
            'guests.max' => __('We quote up to 2,000 guests — please call us for anything larger.'),
            'privacy_consent.accepted' => __('Please tick the box to say you have read our privacy notice.'),
            'event_date.after_or_equal' => __('Please choose a date in the future.'),
        ];
    }
}
