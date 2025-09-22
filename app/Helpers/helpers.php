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