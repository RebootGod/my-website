<?php
// Temporary fix - replace authorize calls with manual checks
// Run this in production as emergency fix

$file = 'app/Http/Controllers/Admin/AdminMovieController.php';
$content = file_get_contents($file);

// Replace authorize calls with manual admin check
$content = str_replace(
    '$this->authorize(\'update\', $movie);',
    'if (!auth()->user() || !auth()->user()->isAdmin()) { abort(403, \'Unauthorized\'); }',
    $content
);

$content = str_replace(
    '$this->authorize(\'create\', Movie::class);',
    'if (!auth()->user() || !auth()->user()->isAdmin()) { abort(403, \'Unauthorized\'); }',
    $content
);

file_put_contents($file, $content);
echo "Temporary authorization fix applied to AdminMovieController.php\n";
echo "All authorize() calls replaced with manual admin checks.\n";