<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMovieRequest extends FormRequest
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
        $movieId = $this->route('movie')?->id;

        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'year' => 'nullable|integer|min:1888|max:' . (date('Y') + 5),
            'overview' => 'nullable|string|max:2000',
            'release_date' => 'nullable|date|before_or_equal:today',
            'runtime' => 'nullable|integer|min:1|max:1000',
            'poster' => 'nullable|image|mimes:jpeg,png,webp|max:5120', // 5MB
            'backdrop_path' => 'nullable|string|max:255',
            'vote_average' => 'nullable|numeric|min:0|max:10',
            'vote_count' => 'nullable|integer|min:0',
            'popularity' => 'nullable|numeric|min:0',
            'original_language' => 'nullable|string|max:10',
            'original_title' => 'nullable|string|max:255',
            'embed_url' => 'nullable|url|max:1000',
            'download_url' => 'nullable|url|max:1000',
            'quality' => 'nullable|in:CAM,TS,HD,FHD,4K',
            'status' => 'required|in:draft,published,archived',
            'is_featured' => 'boolean',
            'tmdb_id' => [
                'nullable',
                'integer',
                Rule::unique('movies', 'tmdb_id')->ignore($movieId)
            ],
            'genre_ids' => 'nullable|array',
            'genre_ids.*' => 'integer|exists:genres,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Movie title is required.',
            'title.unique' => 'A movie with this title already exists.',
            'title.max' => 'Movie title cannot exceed 255 characters.',
            'overview.max' => 'Overview cannot exceed 2000 characters.',
            'release_date.before_or_equal' => 'Release date cannot be in the future.',
            'runtime.min' => 'Runtime must be at least 1 minute.',
            'runtime.max' => 'Runtime cannot exceed 1000 minutes.',
            'poster.image' => 'Poster must be a valid image file.',
            'poster.mimes' => 'Poster must be a JPEG, PNG, or WebP image.',
            'poster.max' => 'Poster file size cannot exceed 5MB.',
            'vote_average.min' => 'Vote average cannot be less than 0.',
            'vote_average.max' => 'Vote average cannot be greater than 10.',
            'vote_count.min' => 'Vote count cannot be negative.',
            'popularity.min' => 'Popularity cannot be negative.',
            'original_language.max' => 'Original language code cannot exceed 10 characters.',
            'original_title.max' => 'Original title cannot exceed 255 characters.',
            'embed_url.url' => 'Embed URL must be a valid URL.',
            'embed_url.max' => 'Embed URL cannot exceed 1000 characters.',
            'download_url.url' => 'Download URL must be a valid URL.',
            'download_url.max' => 'Download URL cannot exceed 1000 characters.',
            'quality.in' => 'Quality must be one of: CAM, TS, HD, FHD, 4K.',
            'status.required' => 'Movie status is required.',
            'status.in' => 'Status must be one of: draft, published, archived.',
            'tmdb_id.unique' => 'A movie with this TMDB ID already exists.',
            'genre_ids.array' => 'Genres must be provided as an array.',
            'genre_ids.*.exists' => 'One or more selected genres do not exist.'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $movieId = $this->route('movie')?->id;
            
            // Check if combination of title and year already exists (excluding current movie)
            $query = \App\Models\Movie::where('title', $this->title)
                ->where('id', '!=', $movieId);
            
            if ($this->filled('year')) {
                $query->where('year', $this->year);
            } else {
                $query->whereNull('year');
            }
            
            if ($query->exists()) {
                $validator->errors()->add(
                    'title',
                    $this->filled('year') 
                        ? "A movie with this title already exists for the year {$this->year}."
                        : "A movie with this title already exists (without year)."
                );
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'tmdb_id' => 'TMDB ID',
            'vote_average' => 'rating',
            'vote_count' => 'vote count',
            'original_language' => 'original language',
            'original_title' => 'original title',
            'embed_url' => 'embed URL',
            'is_featured' => 'featured status',
            'genre_ids' => 'genres',
            'genre_ids.*' => 'genre'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string 'true'/'false' to boolean for is_featured
        if ($this->has('is_featured')) {
            $this->merge([
                'is_featured' => filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        // Ensure genre_ids is an array
        if ($this->has('genre_ids') && !is_array($this->genre_ids)) {
            $this->merge([
                'genre_ids' => $this->genre_ids ? explode(',', $this->genre_ids) : []
            ]);
        }

        // Clean numeric values
        if ($this->has('vote_average') && $this->vote_average === '') {
            $this->merge(['vote_average' => null]);
        }

        if ($this->has('vote_count') && $this->vote_count === '') {
            $this->merge(['vote_count' => null]);
        }

        if ($this->has('popularity') && $this->popularity === '') {
            $this->merge(['popularity' => null]);
        }

        if ($this->has('runtime') && $this->runtime === '') {
            $this->merge(['runtime' => null]);
        }

        if ($this->has('tmdb_id') && $this->tmdb_id === '') {
            $this->merge(['tmdb_id' => null]);
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