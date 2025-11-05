<?php

namespace App\Http\Requests\ServiceRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateServiceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(['cleaning', 'cooking', 'laundry', 'babysitting', 'gardening'])],
            'workload_size' => ['required', 'string', Rule::in(['small', 'medium', 'large'])],
            'frequency' => ['required', 'string', Rule::in(['once', 'weekly', 'biweekly', 'monthly'])],
            'urgency' => ['required', 'string', Rule::in(['low', 'medium', 'high'])],
            'description' => ['nullable', 'string', 'max:1000'],
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
            'category.in' => 'The category must be one of: cleaning, cooking, laundry, babysitting, gardening.',
            'workload_size.in' => 'The workload size must be one of: small, medium, large.',
            'frequency.in' => 'The frequency must be one of: once, weekly, biweekly, monthly.',
            'urgency.in' => 'The urgency must be one of: low, medium, high.',
        ];
    }
}

