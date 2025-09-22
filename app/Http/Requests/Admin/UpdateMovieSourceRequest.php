<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovieSourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('manage movie sources');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'source_name' => 'required|string|max:100',
            'embed_url' => 'required|url|max:1000',
            'quality' => 'required|in:CAM,TS,HD,FHD,4K',
            'priority' => 'nullable|integer|min:0|max:999',
            'is_active' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'source_name.required' => 'Source name is required.',
            'source_name.max' => 'Source name cannot exceed 100 characters.',
            'embed_url.required' => 'Embed URL is required.',
            'embed_url.url' => 'Embed URL must be a valid URL.',
            'embed_url.max' => 'Embed URL cannot exceed 1000 characters.',
            'quality.required' => 'Quality is required.',
            'quality.in' => 'Quality must be one of: CAM, TS, HD, FHD, 4K.',
            'priority.integer' => 'Priority must be a number.',
            'priority.min' => 'Priority cannot be less than 0.',
            'priority.max' => 'Priority cannot exceed 999.',
            'is_active.boolean' => 'Active status must be true or false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'source_name' => 'source name',
            'embed_url' => 'embed URL',
            'is_active' => 'active status'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Keep existing priority if not provided
        if (!$this->has('priority') || $this->priority === '') {
            $currentSource = $this->route('source');
            $this->merge(['priority' => $currentSource?->priority ?? 0]);
        }

        // Convert string 'true'/'false' to boolean for is_active
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)
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
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}