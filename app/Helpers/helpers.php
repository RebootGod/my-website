<?php

if (!function_exists('encrypt_url')) {
    function encrypt_url($url) {
        return \Illuminate\Support\Facades\Crypt::encryptString($url);
    }
}

if (!function_exists('decrypt_url')) {
    function decrypt_url($encryptedUrl) {
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($encryptedUrl);
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (!function_exists('safe_asset_version')) {
    /**
     * Generate asset URL with safe version parameter
     * Prevents filemtime() errors when files don't exist
     * 
     * @param string $path Asset path
     * @return string Asset URL with version parameter
     */
    function safe_asset_version($path) {
        $fullPath = public_path($path);
        
        if (file_exists($fullPath)) {
            try {
                $version = filemtime($fullPath);
            } catch (\Exception $e) {
                $version = time(); // Fallback to current time
            }
        } else {
            $version = time(); // Use current time for cache busting
        }
        
        return asset($path) . '?v=' . $version;
    }
}