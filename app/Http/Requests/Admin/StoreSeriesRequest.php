<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeriesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'poster_url' => 'nullable|url',
            'backdrop_url' => 'nullable|url',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'rating' => 'nullable|numeric|min:0|max:10',
            'status' => 'required|in:published,draft',
            'tmdb_id' => 'nullable|integer|unique:series,tmdb_id',
            'genre_ids' => 'nullable|array',
            'genre_ids.*' => 'exists:genres,id',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Title is required.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'year.integer' => 'Year must be a valid number.',
            'year.min' => 'Year must be at least 1900.',
            'year.max' => 'Year cannot be more than ' . (date('Y') + 10) . '.',
            'duration.integer' => 'Duration must be a valid number.',
            'duration.min' => 'Duration must be at least 1 minute.',
            'rating.numeric' => 'Rating must be a valid number.',
            'rating.min' => 'Rating must be at least 0.',
            'rating.max' => 'Rating cannot exceed 10.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be either published or draft.',
            'tmdb_id.unique' => 'A series with this TMDB ID already exists.',
            'genre_ids.array' => 'Genres must be an array.',
            'genre_ids.*.exists' => 'One or more selected genres are invalid.',
            'poster.image' => 'Poster must be an image file.',
            'poster.mimes' => 'Poster must be a JPEG, PNG, JPG, or GIF file.',
            'poster.max' => 'Poster file size cannot exceed 2MB.',
        ];
    }
}
