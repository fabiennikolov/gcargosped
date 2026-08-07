<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:180'],
            'cargo_type' => ['nullable', 'string', 'max:120'],
            'origin' => ['nullable', 'string', 'max:160'],
            'destination' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:4000'],
            'source' => ['required', Rule::in(['offer', 'contact', 'service'])],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],

            // Honeypot: a real user never sees this field, so anything in it is
            // a bot. Validated as "must be empty" rather than silently ignored.
            'website' => ['nullable', 'prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Моля, въведете име.',
            'phone.required' => 'Моля, въведете телефон.',
            'email.email' => 'Имейлът изглежда невалиден.',
            'website.prohibited' => 'Заявката не може да бъде обработена.',
        ];
    }
}
