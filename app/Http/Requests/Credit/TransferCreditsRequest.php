<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class TransferCreditsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'to_user_id' => ['required', 'exists:users,id', 'different:from_user_id'],
            'amount' => ['required', 'integer', 'min:1'],
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
            'to_user_id.required' => 'Recipient user ID is required',
            'to_user_id.exists' => 'Recipient user not found',
            'to_user_id.different' => 'Cannot transfer credits to yourself',
            'amount.required' => 'Amount is required',
            'amount.integer' => 'Amount must be an integer',
            'amount.min' => 'Amount must be at least 1',
            'reason.required' => 'Reason is required',
            'reason.max' => 'Reason cannot exceed 255 characters',
        ];
    }
}
