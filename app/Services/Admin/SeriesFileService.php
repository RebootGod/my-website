<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SeriesFileService
{
    protected $disk = 'public';
    protected $posterPath = 'series/posters';

    /**
     * Upload series poster
     */
    public function uploadPoster($file, $oldPath = null)
    {
        try {
            // Delete old poster if exists
            if ($oldPath && Storage::disk($this->disk)->exists($oldPath)) {
                Storage::disk($this->disk)->delete($oldPath);
            }

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($this->posterPath, $filename, $this->disk);

            return [
                'success' => true,
                'path' => $path,
                'url' => Storage::disk($this->disk)->url($path)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to upload series poster', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to upload poster: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete series poster
     */
    public function deletePoster($path)
    {
        try {
            if ($path && Storage::disk($this->disk)->exists($path)) {
                Storage::disk($this->disk)->delete($path);
            }

            return [
                'success' => true
            ];

        } catch (\Exception $e) {
            Log::error('Failed to delete series poster', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete poster: ' . $e->getMessage()
            ];
        }
    }
}
