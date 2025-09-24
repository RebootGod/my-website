<?php
// Fix all remaining ->can() issues in controllers
echo "=== FIXING ALL CONTROLLER ->can() ISSUES ===\n";

$controllers = glob('app/Http/Controllers/*.php');
$controllers = array_merge($controllers, glob('app/Http/Controllers/*/*.php'));

$fixedFiles = [];

foreach ($controllers as $controller) {
    $content = file_get_contents($controller);

    if (strpos($content, '->can(') !== false) {
        echo "Fixing: " . $controller . "\n";

        // Backup
        file_put_contents($controller . '.backup', $content);

        // Fix ->can() issues by replacing with ->isAdmin()
        $fixed = preg_replace(
            '/auth\(\)->user\(\)->can\([^)]+\)/',
            'auth()->user()->isAdmin()',
            $content
        );

        // Also fix cases without auth()->user() prefix
        $fixed = preg_replace(
            '/\$user->can\([^)]+\)/',
            '$user->isAdmin()',
            $fixed
        );

        file_put_contents($controller, $fixed);
        $fixedFiles[] = $controller;
    }
}

if (empty($fixedFiles)) {
    echo "✅ No controllers need fixing\n";
} else {
    echo "✅ Fixed " . count($fixedFiles) . " controllers:\n";
    foreach ($fixedFiles as $file) {
        echo "   - " . $file . "\n";
    }
}

echo "\n=== CONTROLLER FIX COMPLETE ===\n";