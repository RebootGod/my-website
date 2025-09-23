<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TMDBBulkImportRequest extends FormRequest
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
            'tmdb_ids' => 'required|array|min:1|max:50',
            'tmdb_ids.*' => 'integer|min:1',
            'download_posters' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tmdb_ids.required' => 'At least one TMDB ID is required.',
            'tmdb_ids.array' => 'TMDB IDs must be provided as an array.',
            'tmdb_ids.min' => 'At least one TMDB ID is required.',
            'tmdb_ids.max' => 'Cannot import more than 50 movies at once.',
            'tmdb_ids.*.integer' => 'Each TMDB ID must be a valid number.',
            'tmdb_ids.*.min' => 'Each TMDB ID must be greater than 0.',
            'download_posters.boolean' => 'Download posters option must be true or false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'tmdb_ids' => 'TMDB IDs',
            'tmdb_ids.*' => 'TMDB ID',
            'download_posters' => 'download posters option'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string 'true'/'false' to boolean for download_posters
        if ($this->has('download_posters')) {
            $this->merge([
                'download_posters' => filter_var($this->download_posters, FILTER_VALIDATE_BOOLEAN)
            ]);
        } else {
            $this->merge(['download_posters' => true]);
        }

        // Ensure tmdb_ids are integers and remove duplicates
        if ($this->has('tmdb_ids') && is_array($this->tmdb_ids)) {
            $cleanIds = array_unique(array_map('intval', array_filter($this->tmdb_ids)));
            $this->merge(['tmdb_ids' => array_values($cleanIds)]);
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