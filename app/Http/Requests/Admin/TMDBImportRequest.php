<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TMDBImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tmdb_id' => 'required|integer|min:1',
            'download_poster' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tmdb_id.required' => 'TMDB ID is required.',
            'tmdb_id.integer' => 'TMDB ID must be a valid number.',
            'tmdb_id.min' => 'TMDB ID must be greater than 0.',
            'download_poster.boolean' => 'Download poster option must be true or false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'tmdb_id' => 'TMDB ID',
            'download_poster' => 'download poster option'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string 'true'/'false' to boolean for download_poster
        if ($this->has('download_poster')) {
            $this->merge([
                'download_poster' => filter_var($this->download_poster, FILTER_VALIDATE_BOOLEAN)
            ]);
        } else {
            $this->merge(['download_poster' => true]);
        }

        // Ensure tmdb_id is integer
        if ($this->has('tmdb_id')) {
            $this->merge(['tmdb_id' => (int) $this->tmdb_id]);
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}