<?php
// Simple debug without Laravel bootstrap
echo "=== SIMPLE DEBUGGING ===\n";

echo "1. File existence check:\n";
$files = [
    'app/Http/Controllers/Controller.php',
    'app/Http/Controllers/Admin/AdminMovieController.php',
    'app/Providers/AdminServiceProvider.php',
    'bootstrap/providers.php'
];

foreach ($files as $file) {
    echo "   - {$file}: " . (file_exists($file) ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n2. Controller.php content:\n";
$controller = file_get_contents('app/Http/Controllers/Controller.php');
echo "   - Contains AuthorizesRequests: " . (strpos($controller, 'AuthorizesRequests') !== false ? 'YES' : 'NO') . "\n";

echo "\n3. AdminServiceProvider registered:\n";
$providers = file_get_contents('bootstrap/providers.php');
echo "   - Contains AdminServiceProvider: " . (strpos($providers, 'AdminServiceProvider') !== false ? 'YES' : 'NO') . "\n";

echo "\n4. AdminMovieController storeSource method:\n";
$adminController = file_get_contents('app/Http/Controllers/Admin/AdminMovieController.php');
$lines = explode("\n", $adminController);
$inStoreSource = false;
$storeSourceLines = [];

foreach ($lines as $i => $line) {
    if (strpos($line, 'public function storeSource') !== false) {
        $inStoreSource = true;
        $lineCount = 0;
    }

    if ($inStoreSource && $lineCount < 10) {
        $storeSourceLines[] = ($i + 1) . ": " . trim($line);
        $lineCount++;

        if (strpos($line, '}') !== false && $lineCount > 3) {
            break;
        }
    }
}

foreach ($storeSourceLines as $line) {
    echo "   " . $line . "\n";
}

echo "\n=== SIMPLE DEBUG COMPLETE ===\n";