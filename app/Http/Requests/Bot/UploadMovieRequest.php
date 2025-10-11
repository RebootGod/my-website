<?php

namespace App\Http\Requests\Bot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request: Upload Movie from Telegram Bot
 * 
 * Security: Validates all inputs, prevents injection attacks
 * OWASP: Protected against XSS, SQL Injection via validation
 * 
 * @package App\Http\Requests\Bot
 */
class UploadMovieRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by AuthenticateTelegramBot middleware
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
            'embed_url' => [
                'required',
                'url',
                'max:1000',
                'starts_with:https://'
            ],
            'download_url' => [
                'nullable',
                'url',
                'max:1000',
                'starts_with:https://'
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
            'embed_url.required' => 'Embed URL is required',
            'embed_url.url' => 'Embed URL must be a valid URL',
            'embed_url.max' => 'Embed URL cannot exceed 1000 characters',
            'embed_url.starts_with' => 'Embed URL must use HTTPS protocol',
            'download_url.url' => 'Download URL must be a valid URL',
            'download_url.max' => 'Download URL cannot exceed 1000 characters',
            'download_url.starts_with' => 'Download URL must use HTTPS protocol',
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
        // Sanitize inputs to prevent XSS
        if ($this->has('embed_url')) {
            $this->merge([
                'embed_url' => filter_var($this->embed_url, FILTER_SANITIZE_URL)
            ]);
        }

        if ($this->has('download_url')) {
            $this->merge([
                'download_url' => filter_var($this->download_url, FILTER_SANITIZE_URL)
            ]);
        }

        if ($this->has('telegram_username')) {
            $this->merge([
                'telegram_username' => strip_tags($this->telegram_username)
            ]);
        }
    }
}
