<?php

namespace App\Http\Requests\Api;

use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateDynamicQrisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_id' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'reference' => ['required', 'string', 'max:100'],
            'fee_type' => ['nullable', 'string', 'in:none,fixed,percentage'],
            'fee_value' => ['nullable', 'numeric', 'min:0'],
            'fee_mode' => ['nullable', 'string', 'in:absorbed,charged_to_customer'],
            'expiry_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error('Validation failed', $validator->errors(), 422)
        );
    }
}
