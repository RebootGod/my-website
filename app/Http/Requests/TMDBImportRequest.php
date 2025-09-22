<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * TMDB Import Request
 * Centralizes validation for TMDB import operations
 */
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
            'tmdb_id' => [
                'required',
                'integer',
                'min:1',
                'max:999999999'
            ],
            'type' => [
                'nullable',
                'string',
                Rule::in(['movie', 'tv', 'series'])
            ],
            'override_existing' => [
                'nullable',
                'boolean'
            ],
            'import_images' => [
                'nullable',
                'boolean'
            ],
            'import_cast' => [
                'nullable',
                'boolean'
            ],
            'import_crew' => [
                'nullable',
                'boolean'
            ],
            'language' => [
                'nullable',
                'string',
                'max:5',
                'regex:/^[a-z]{2}(-[A-Z]{2})?$/'
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(['draft', 'published', 'archived'])
            ],
            'genre_ids' => [
                'nullable',
                'array'
            ],
            'genre_ids.*' => [
                'integer',
                'exists:genres,id'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tmdb_id.required' => 'TMDB ID is required for import.',
            'tmdb_id.integer' => 'TMDB ID must be a valid number.',
            'tmdb_id.min' => 'TMDB ID must be at least 1.',
            'tmdb_id.max' => 'TMDB ID is too large.',
            'type.in' => 'Content type must be movie, tv, or series.',
            'language.regex' => 'Language must be in format: en or en-US.',
            'status.in' => 'Status must be draft, published, or archived.',
            'genre_ids.array' => 'Genre IDs must be provided as an array.',
            'genre_ids.*.integer' => 'Each genre ID must be a valid number.',
            'genre_ids.*.exists' => 'One or more genre IDs do not exist.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'tmdb_id' => 'TMDB ID',
            'type' => 'content type',
            'override_existing' => 'override existing content',
            'import_images' => 'import images',
            'import_cast' => 'import cast information',
            'import_crew' => 'import crew information',
            'language' => 'language code',
            'status' => 'publication status',
            'genre_ids' => 'genre selections',
            'genre_ids.*' => 'genre ID'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default type if not provided
        if (!$this->has('type') || $this->type === null) {
            $this->merge([
                'type' => 'movie'
            ]);
        }

        // Set default language
        if (!$this->has('language') || $this->language === null) {
            $this->merge([
                'language' => 'en-US'
            ]);
        }

        // Set default status
        if (!$this->has('status') || $this->status === null) {
            $this->merge([
                'status' => 'draft'
            ]);
        }

        // Convert boolean fields
        $booleanFields = ['override_existing', 'import_images', 'import_cast', 'import_crew'];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->$field, FILTER_VALIDATE_BOOLEAN)
                ]);
            } else {
                $this->merge([
                    $field => false
                ]);
            }
        }

        // Ensure genre_ids is an array
        if ($this->has('genre_ids') && !is_array($this->genre_ids)) {
            if (is_string($this->genre_ids)) {
                $this->merge([
                    'genre_ids' => explode(',', $this->genre_ids)
                ]);
            } else {
                $this->merge([
                    'genre_ids' => []
                ]);
            }
        }

        // Convert TMDB ID to integer if it's a string
        if ($this->has('tmdb_id') && is_string($this->tmdb_id)) {
            $this->merge([
                'tmdb_id' => (int) $this->tmdb_id
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
                    'message' => 'Import validation failed',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }

    /**
     * Get the validated data with proper type casting
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Ensure proper type casting for specific fields
        if (is_array($validated)) {
            if (isset($validated['tmdb_id'])) {
                $validated['tmdb_id'] = (int) $validated['tmdb_id'];
            }

            if (isset($validated['genre_ids'])) {
                $validated['genre_ids'] = array_map('intval', $validated['genre_ids']);
            }
        }

        return $validated;
    }
}