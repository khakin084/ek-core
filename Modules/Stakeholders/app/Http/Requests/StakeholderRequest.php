<?php

namespace Modules\Stakeholders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StakeholderRequest extends FormRequest
{
    public function authorize(): bool
    {        
        return userCan('stakeholders', 'read_write');
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(['INDIVIDUAL', 'ORGANIZATION'])],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'alt_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string', 'max:191'],

            'industry_id' => ['nullable', 'uuid'],
            'tin' => ['nullable', 'string', 'max:50'],
            'vrn' => ['nullable', 'string', 'max:50'],

            'first_name' => ['nullable', 'required_if:kind,INDIVIDUAL', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'surname' => ['nullable', 'required_if:kind,INDIVIDUAL', 'string', 'max:50'],
            'gender' => ['nullable', 'required_if:kind,INDIVIDUAL', Rule::in(['MALE', 'FEMALE'])],
            'date_of_birth' => ['nullable', 'date'],
        ];
    }

    // Mirrors what the show endpoint already returns — business_details or
    // personal_details nested under one key, never both, chosen by kind.
    public function toPayload(): array
    {
        $payload = $this->only([
            'kind',
            'name',
            'description',
            'phone',
            'alt_phone',
            'email',
            'address',
        ]);

        $payload['business_details'] = $this->input('kind') === 'ORGANIZATION'
            ? $this->only(['industry_id', 'tin', 'vrn'])
            : null;

        $payload['personal_details'] = $this->input('kind') === 'INDIVIDUAL'
            ? $this->only(['first_name', 'middle_name', 'surname', 'gender', 'date_of_birth'])
            : null;

        return $payload;
    }
}