<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class AddCreditsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware and policy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000'],
            'reason' => ['required', 'string', 'max:255'],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'User not found',
            'amount.required' => 'Amount is required',
            'amount.integer' => 'Amount must be an integer',
            'amount.min' => 'Amount must be at least 1',
            'amount.max' => 'Amount cannot exceed 1,000,000',
            'reason.required' => 'Reason is required',
            'reason.max' => 'Reason cannot exceed 255 characters',
        ];
    }
}
