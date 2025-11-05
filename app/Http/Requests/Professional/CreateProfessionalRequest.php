<?php

namespace App\Http\Requests\Professional;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProfessionalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Policy will handle authorization
    }

    /**
     * Prepare the data for validation.
     * Decode JSON strings for form-data requests.
     */
    protected function prepareForValidation(): void
    {
        // Decode schedule if it's a JSON string (from form-data)
        if ($this->has('schedule') && is_string($this->schedule)) {
            try {
                $decoded = json_decode($this->schedule, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->merge(['schedule' => $decoded]);
                }
            } catch (\Exception $e) {
                // If decoding fails, leave as is and let validation handle it
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bio' => ['required', 'string', 'min:50', 'max:2000'],
            'skills' => ['required', 'array', 'min:1', 'max:20'],
            'skills.*' => ['required', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'schedule' => ['nullable', 'array'],
            'schedule.*.available' => ['boolean'],
            'schedule.*.hours' => ['array'],
            'schedule.*.hours.*' => ['string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]-([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
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
            'bio.required' => 'A biografia é obrigatória.',
            'bio.min' => 'A biografia deve ter pelo menos 50 caracteres.',
            'bio.max' => 'A biografia não pode ter mais de 2000 caracteres.',
            'skills.required' => 'Você deve informar pelo menos uma habilidade.',
            'skills.min' => 'Você deve informar pelo menos uma habilidade.',
            'skills.max' => 'Você pode informar no máximo 20 habilidades.',
            'photo.image' => 'A foto deve ser uma imagem válida.',
            'photo.max' => 'A foto não pode ter mais de 2MB.',
        ];
    }
}

