<?php

namespace App\Http\Requests\Bot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request: Upload Season from Telegram Bot
 * 
 * Security: Validates all inputs
 * OWASP: Protected against injection attacks
 * 
 * @package App\Http\Requests\Bot
 */
class UploadSeasonRequest extends FormRequest
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
            'tmdb_id' => [
                'required',
                'integer',
                'min:1',
                'max:9999999'
            ],
            'season_number' => [
                'required',
                'integer',
                'min:0',
                'max:100'
            ],
            'telegram_user_id' => [
                'nullable',
                'integer'
            ],
            'telegram_username' => [
                'nullable',
                'string',
                'max:255'
            ]
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
            'tmdb_id.required' => 'TMDB ID is required',
            'tmdb_id.integer' => 'TMDB ID must be a valid number',
            'tmdb_id.min' => 'TMDB ID must be at least 1',
            'tmdb_id.max' => 'TMDB ID is invalid (too large)',
            'season_number.required' => 'Season number is required',
            'season_number.integer' => 'Season number must be a valid number',
            'season_number.min' => 'Season number must be at least 0',
            'season_number.max' => 'Season number cannot exceed 100',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid input data',
                    'details' => $validator->errors()->toArray()
                ]
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('telegram_username')) {
            $this->merge([
                'telegram_username' => strip_tags($this->telegram_username)
            ]);
        }
    }
}
