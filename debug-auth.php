<?php
// Debug script untuk cek authorization issue di production
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

echo "=== AUTHORIZATION DEBUG ===\n";

// 1. Check Controller class
echo "1. Controller class analysis:\n";
$reflection = new ReflectionClass('App\Http\Controllers\Controller');
echo "   - File: " . $reflection->getFileName() . "\n";
echo "   - Uses AuthorizesRequests: " . (in_array('Illuminate\Foundation\Auth\Access\AuthorizesRequests', $reflection->getTraitNames()) ? 'YES' : 'NO') . "\n";
echo "   - Has authorize method: " . ($reflection->hasMethod('authorize') ? 'YES' : 'NO') . "\n";

// 2. Check AdminMovieController
echo "\n2. AdminMovieController analysis:\n";
$adminReflection = new ReflectionClass('App\Http\Controllers\Admin\AdminMovieController');
echo "   - File: " . $adminReflection->getFileName() . "\n";
echo "   - Has authorize method: " . ($adminReflection->hasMethod('authorize') ? 'YES' : 'NO') . "\n";
echo "   - Has storeSource method: " . ($adminReflection->hasMethod('storeSource') ? 'YES' : 'NO') . "\n";

// 3. Check storeSource method content
if ($adminReflection->hasMethod('storeSource')) {
    $method = $adminReflection->getMethod('storeSource');
    echo "   - storeSource line: " . $method->getStartLine() . "\n";

    $file = file($adminReflection->getFileName());
    $methodLines = array_slice($file, $method->getStartLine() - 1, 10);
    echo "   - First 10 lines of storeSource:\n";
    foreach ($methodLines as $i => $line) {
        echo "     " . ($method->getStartLine() + $i) . ": " . rtrim($line) . "\n";
    }
}

// 4. Check Movie Policy
echo "\n3. MoviePolicy analysis:\n";
try {
    $policyReflection = new ReflectionClass('App\Policies\MoviePolicy');
    echo "   - File: " . $policyReflection->getFileName() . "\n";
    echo "   - Has update method: " . ($policyReflection->hasMethod('update') ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
}

// 5. Check if policies are registered
echo "\n4. AuthServiceProvider analysis:\n";
try {
    $authProvider = new ReflectionClass('App\Providers\AuthServiceProvider');
    $file = file($authProvider->getFileName());
    $content = implode('', $file);
    echo "   - Contains MoviePolicy: " . (strpos($content, 'MoviePolicy') !== false ? 'YES' : 'NO') . "\n";
    echo "   - Contains Movie::class: " . (strpos($content, 'Movie::class') !== false ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";