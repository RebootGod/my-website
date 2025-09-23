<?php
// Quick fix for 500 error - disable AdminServiceProvider temporarily
echo "Applying quick fix for 500 error...\n";

$providersFile = 'bootstrap/providers.php';
$content = file_get_contents($providersFile);

// Backup
file_put_contents($providersFile . '.backup', $content);

// Comment out AdminServiceProvider
$fixed = str_replace(
    'App\Providers\AdminServiceProvider::class,',
    '// App\Providers\AdminServiceProvider::class, // TEMPORARILY DISABLED',
    $content
);

file_put_contents($providersFile, $fixed);

echo "AdminServiceProvider temporarily disabled.\n";
echo "Backup created: {$providersFile}.backup\n";
echo "Now clear caches: php artisan optimize:clear\n";