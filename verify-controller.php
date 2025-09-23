<?php
// Verify Controller.php di production
echo "=== CONTROLLER VERIFICATION ===\n";

// 1. Check file Controller.php langsung
$controllerPath = __DIR__ . '/app/Http/Controllers/Controller.php';
echo "1. Reading Controller.php file directly:\n";
echo "   Path: $controllerPath\n";

if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);
    echo "   File exists: YES\n";
    echo "   Contains AuthorizesRequests: " . (strpos($content, 'AuthorizesRequests') !== false ? 'YES' : 'NO') . "\n";
    echo "   Contains ValidatesRequests: " . (strpos($content, 'ValidatesRequests') !== false ? 'YES' : 'NO') . "\n";
    echo "   File content:\n";
    echo "   " . str_replace("\n", "\n   ", $content) . "\n";
} else {
    echo "   File exists: NO\n";
}

// 2. Check via reflection (what PHP actually loads)
echo "\n2. Checking what PHP loads via reflection:\n";
try {
    require_once 'vendor/autoload.php';

    $reflection = new ReflectionClass('App\Http\Controllers\Controller');
    echo "   Loaded from: " . $reflection->getFileName() . "\n";
    echo "   Traits: " . implode(', ', $reflection->getTraitNames()) . "\n";
    echo "   Has authorize method: " . ($reflection->hasMethod('authorize') ? 'YES' : 'NO') . "\n";

    // Check parent class
    $parent = $reflection->getParentClass();
    if ($parent) {
        echo "   Parent class: " . $parent->getName() . "\n";
        echo "   Parent traits: " . implode(', ', $parent->getTraitNames()) . "\n";
    }

} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// 3. Check AdminMovieController
echo "\n3. Checking AdminMovieController:\n";
try {
    $adminReflection = new ReflectionClass('App\Http\Controllers\Admin\AdminMovieController');
    echo "   Loaded from: " . $adminReflection->getFileName() . "\n";
    echo "   Has authorize method: " . ($adminReflection->hasMethod('authorize') ? 'YES' : 'NO') . "\n";

    // Check line 396 (storeSource method)
    if ($adminReflection->hasMethod('storeSource')) {
        $method = $adminReflection->getMethod('storeSource');
        echo "   storeSource starts at line: " . $method->getStartLine() . "\n";

        $file = file($adminReflection->getFileName());
        $methodLine = $method->getStartLine();
        echo "   Lines around storeSource:\n";
        for ($i = $methodLine - 1; $i < $methodLine + 5; $i++) {
            if (isset($file[$i])) {
                echo "     " . ($i + 1) . ": " . rtrim($file[$i]) . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// 4. Test authorize method directly
echo "\n4. Testing authorize method availability:\n";
try {
    $controller = new App\Http\Controllers\Admin\AdminMovieController(
        new App\Services\Admin\MovieTMDBService(),
        new App\Services\Admin\MovieSourceService(),
        new App\Services\Admin\MovieFileService(),
        new App\Services\Admin\MovieReportService()
    );
    echo "   Controller instantiated: YES\n";
    echo "   Has authorize method: " . (method_exists($controller, 'authorize') ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "   ERROR creating controller: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";