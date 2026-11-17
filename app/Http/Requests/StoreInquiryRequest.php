<?php

namespace App\Http\Requests;

use App\Support\InquiryForms;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The fields come from the form as the admin arranged it (Форми), so what
     * is required here is exactly what the visitor saw marked with a star.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $source = (string) $this->input('source');

        return [
            ...(isset(InquiryForms::SOURCE_VARIANT[$source]) ? InquiryForms::rules($source) : []),
            'extra' => ['nullable', 'array'],
            'source' => ['required', Rule::in(array_keys(InquiryForms::SOURCE_VARIANT))],
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
            'email.required' => 'Моля, въведете имейл.',
            'email.email' => 'Имейлът изглежда невалиден.',
            'cargo_quantity.integer' => 'Въведете цяло число.',
            'cargo_quantity.min' => 'Въведете поне 1.',
            'cargo_quantity.max' => 'Числото е твърде голямо.',
            'loading_date.date' => 'Изберете дата.',
            'required' => 'Моля, попълнете полето.',
            'accepted' => 'Моля, отбележете полето.',
            'date' => 'Изберете дата.',
            'numeric' => 'Въведете число.',
            'in' => 'Изберете от списъка.',
            'website.prohibited' => 'Заявката не може да бъде обработена.',
        ];
    }
}
