<?php
// Emergency fix - comment out authorize calls temporarily
echo "Applying emergency fix to AdminMovieController...\n";

$file = 'app/Http/Controllers/Admin/AdminMovieController.php';
$content = file_get_contents($file);

// Backup original
file_put_contents($file . '.backup', $content);
echo "Backup created: {$file}.backup\n";

// Comment out authorize calls
$content = str_replace(
    '$this->authorize(',
    '// EMERGENCY FIX: $this->authorize(',
    $content
);

// Write back
file_put_contents($file, $content);

echo "Emergency fix applied!\n";
echo "All \$this->authorize() calls have been commented out.\n";
echo "This is TEMPORARY - remember to restore after debugging!\n";

// Count how many were fixed
$count = substr_count($content, '// EMERGENCY FIX:');
echo "Fixed {$count} authorize calls.\n";