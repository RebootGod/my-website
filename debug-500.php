<?php
// Debug 500 error script
echo "=== DEBUG 500 ERROR ===\n";

echo "1. Check Laravel logs:\n";
$logFile = 'storage/logs/laravel.log';
if (file_exists($logFile)) {
    echo "   - Log file exists: YES\n";
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLines = array_slice($lines, -20); // Last 20 lines
    echo "   - Recent log entries:\n";
    foreach ($recentLines as $line) {
        if (!empty(trim($line))) {
            echo "     " . trim($line) . "\n";
        }
    }
} else {
    echo "   - Log file exists: NO\n";
}

echo "\n2. Check syntax errors in recent Form Request changes:\n";
$formRequests = [
    'app/Http/Requests/Admin/StoreMovieRequest.php',
    'app/Http/Requests/Admin/UpdateMovieRequest.php',
    'app/Http/Requests/Admin/StoreMovieSourceRequest.php',
    'app/Http/Requests/Admin/UpdateMovieSourceRequest.php',
    'app/Http/Requests/Admin/TMDBImportRequest.php',
    'app/Http/Requests/Admin/TMDBBulkImportRequest.php'
];

foreach ($formRequests as $file) {
    if (file_exists($file)) {
        // Check for syntax errors by including the file
        $error = null;
        ob_start();
        $result = @include_once $file;
        $output = ob_get_clean();

        if ($result === false) {
            echo "   ❌ {$file}: SYNTAX ERROR\n";
        } else {
            echo "   ✅ {$file}: OK\n";
        }
    } else {
        echo "   ❓ {$file}: NOT FOUND\n";
    }
}

echo "\n3. Check if AdminServiceProvider is causing issues:\n";
$adminProvider = 'app/Providers/AdminServiceProvider.php';
if (file_exists($adminProvider)) {
    echo "   - AdminServiceProvider exists: YES\n";

    // Check syntax
    $error = null;
    ob_start();
    $result = @include_once $adminProvider;
    $output = ob_get_clean();

    if ($result === false) {
        echo "   ❌ AdminServiceProvider: SYNTAX ERROR\n";
    } else {
        echo "   ✅ AdminServiceProvider: OK\n";
    }
} else {
    echo "   - AdminServiceProvider exists: NO\n";
}

echo "\n4. Check bootstrap/providers.php:\n";
$providers = 'bootstrap/providers.php';
if (file_exists($providers)) {
    echo "   - Providers file exists: YES\n";
    $content = file_get_contents($providers);
    echo "   - Content:\n";
    echo "     " . str_replace("\n", "\n     ", $content) . "\n";
} else {
    echo "   - Providers file exists: NO\n";
}

echo "\n=== DEBUG COMPLETE ===\n";