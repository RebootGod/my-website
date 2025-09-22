<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * TMDB Search Request
 * Centralizes validation for TMDB search operations
 */
class TMDBSearchRequest extends FormRequest
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
            'query' => [
                'required',
                'string',
                'min:2',
                'max:255'
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000'
            ],
            'year' => [
                'nullable',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 5)
            ],
            'include_adult' => [
                'nullable',
                'boolean'
            ],
            'language' => [
                'nullable',
                'string',
                'max:5',
                'regex:/^[a-z]{2}(-[A-Z]{2})?$/'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'query.required' => 'Search query is required.',
            'query.min' => 'Search query must be at least 2 characters.',
            'query.max' => 'Search query cannot exceed 255 characters.',
            'page.integer' => 'Page must be a valid number.',
            'page.min' => 'Page must be at least 1.',
            'page.max' => 'Page cannot exceed 1000.',
            'year.integer' => 'Year must be a valid number.',
            'year.min' => 'Year cannot be before 1900.',
            'year.max' => 'Year cannot be more than 5 years in the future.',
            'language.regex' => 'Language must be in format: en or en-US.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'query' => 'search query',
            'page' => 'page number',
            'year' => 'release year',
            'include_adult' => 'include adult content',
            'language' => 'language code'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize query - trim whitespace and convert to lowercase for consistency
        if ($this->has('query')) {
            $this->merge([
                'query' => trim($this->query)
            ]);
        }

        // Set default page if not provided
        if (!$this->has('page') || $this->page === null) {
            $this->merge([
                'page' => 1
            ]);
        }

        // Convert include_adult to boolean
        if ($this->has('include_adult')) {
            $this->merge([
                'include_adult' => filter_var($this->include_adult, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        // Set default language
        if (!$this->has('language') || $this->language === null) {
            $this->merge([
                'language' => 'en-US'
            ]);
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
                    'message' => 'Search validation failed',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}