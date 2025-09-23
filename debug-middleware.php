<?php
// Debug middleware dan route issues
echo "=== MIDDLEWARE & ROUTE DEBUGGING ===\n";

echo "1. Check AdminMiddleware:\n";
$adminMiddleware = file_get_contents('app/Http/Middleware/AdminMiddleware.php');
echo "   - AdminMiddleware exists: " . (file_exists('app/Http/Middleware/AdminMiddleware.php') ? 'YES' : 'NO') . "\n";

if (strpos($adminMiddleware, 'function handle') !== false) {
    $lines = explode("\n", $adminMiddleware);
    echo "   - AdminMiddleware content:\n";
    $inHandle = false;
    $lineCount = 0;

    foreach ($lines as $i => $line) {
        if (strpos($line, 'function handle') !== false) {
            $inHandle = true;
        }

        if ($inHandle && $lineCount < 15) {
            echo "     " . ($i + 1) . ": " . trim($line) . "\n";
            $lineCount++;

            if (strpos($line, '}') !== false && $lineCount > 3) {
                break;
            }
        }
    }
}

echo "\n2. Check route registration:\n";
$webRoutes = file_get_contents('routes/web.php');
$sourceRouteExists = strpos($webRoutes, '/admin/movies/{movie}/sources') !== false ||
                   strpos($webRoutes, 'admin.movies.sources') !== false;
echo "   - Sources route in web.php: " . ($sourceRouteExists ? 'YES' : 'NO') . "\n";

// Check for Route::resource or similar
if (strpos($webRoutes, 'Route::resource') !== false) {
    echo "   - Uses Route::resource: YES\n";
} else {
    echo "   - Uses Route::resource: NO\n";
}

echo "\n3. Check Form Request validation:\n";
$storeMovieSourceRequest = 'app/Http/Requests/Admin/StoreMovieSourceRequest.php';
echo "   - StoreMovieSourceRequest exists: " . (file_exists($storeMovieSourceRequest) ? 'YES' : 'NO') . "\n";

if (file_exists($storeMovieSourceRequest)) {
    $requestContent = file_get_contents($storeMovieSourceRequest);
    if (strpos($requestContent, 'function authorize') !== false) {
        echo "   - Has authorize() method: YES\n";
        // Extract authorize method
        $lines = explode("\n", $requestContent);
        $inAuthorize = false;

        foreach ($lines as $i => $line) {
            if (strpos($line, 'function authorize') !== false) {
                $inAuthorize = true;
                echo "     Authorize method:\n";
            }

            if ($inAuthorize) {
                echo "     " . ($i + 1) . ": " . trim($line) . "\n";
                if (strpos($line, '}') !== false) {
                    break;
                }
            }
        }
    } else {
        echo "   - Has authorize() method: NO\n";
    }
} else {
    echo "   - Request class missing!\n";
}

echo "\n4. Check CheckPermission middleware:\n";
if (file_exists('app/Http/Middleware/CheckPermission.php')) {
    echo "   - CheckPermission exists: YES\n";
    $checkPermission = file_get_contents('app/Http/Middleware/CheckPermission.php');
    if (strpos($checkPermission, 'abort(403') !== false) {
        echo "   - Contains abort(403): YES - This might be the culprit!\n";

        // Extract the handle method
        $lines = explode("\n", $checkPermission);
        $inHandle = false;

        foreach ($lines as $i => $line) {
            if (strpos($line, 'function handle') !== false) {
                $inHandle = true;
                echo "     CheckPermission handle method:\n";
            }

            if ($inHandle) {
                echo "     " . ($i + 1) . ": " . trim($line) . "\n";
                if (strpos($line, '}') !== false && strpos($line, 'function') === false) {
                    break;
                }
            }
        }
    }
} else {
    echo "   - CheckPermission exists: NO\n";
}

echo "\n=== MIDDLEWARE DEBUG COMPLETE ===\n";