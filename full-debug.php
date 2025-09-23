<?php
// Full debugging script untuk production
echo "=== FULL DEBUGGING ===\n";

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

echo "1. Laravel App Status:\n";
echo "   - App loaded: YES\n";
echo "   - Environment: " . $app->environment() . "\n";

echo "\n2. Service Container Status:\n";
try {
    $movieTMDB = $app->make('App\Services\Admin\MovieTMDBService');
    echo "   - MovieTMDBService: RESOLVED\n";
} catch (Exception $e) {
    echo "   - MovieTMDBService: ERROR - " . $e->getMessage() . "\n";
}

try {
    $movieSource = $app->make('App\Services\Admin\MovieSourceService');
    echo "   - MovieSourceService: RESOLVED\n";
} catch (Exception $e) {
    echo "   - MovieSourceService: ERROR - " . $e->getMessage() . "\n";
}

try {
    $movieFile = $app->make('App\Services\Admin\MovieFileService');
    echo "   - MovieFileService: RESOLVED\n";
} catch (Exception $e) {
    echo "   - MovieFileService: ERROR - " . $e->getMessage() . "\n";
}

try {
    $movieReport = $app->make('App\Services\Admin\MovieReportService');
    echo "   - MovieReportService: RESOLVED\n";
} catch (Exception $e) {
    echo "   - MovieReportService: ERROR - " . $e->getMessage() . "\n";
}

echo "\n3. AdminMovieController Resolution:\n";
try {
    $controller = $app->make('App\Http\Controllers\Admin\AdminMovieController');
    echo "   - Controller created: YES\n";
    echo "   - Has authorize method: " . (method_exists($controller, 'authorize') ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "   - Controller creation: ERROR - " . $e->getMessage() . "\n";
}

echo "\n4. Providers Check:\n";
$providers = $app->getLoadedProviders();
echo "   - AdminServiceProvider loaded: " . (isset($providers['App\Providers\AdminServiceProvider']) ? 'YES' : 'NO') . "\n";
echo "   - AuthServiceProvider loaded: " . (isset($providers['App\Providers\AuthServiceProvider']) ? 'YES' : 'NO') . "\n";

echo "\n5. Movie Policy Check:\n";
try {
    $policy = $app->make('App\Policies\MoviePolicy');
    echo "   - MoviePolicy resolved: YES\n";

    // Test user
    $adminUser = App\Models\User::where('role', 'admin')->first();
    if ($adminUser) {
        echo "   - Admin user found: YES (ID: {$adminUser->id})\n";
        echo "   - Admin user role: {$adminUser->role}\n";
        echo "   - User isAdmin(): " . ($adminUser->isAdmin() ? 'YES' : 'NO') . "\n";

        // Test policy
        $testMovie = App\Models\Movie::first();
        if ($testMovie) {
            echo "   - Test movie found: YES (ID: {$testMovie->id})\n";
            $canUpdate = $policy->update($adminUser, $testMovie);
            echo "   - Policy update() result: " . ($canUpdate ? 'YES' : 'NO') . "\n";
        } else {
            echo "   - Test movie found: NO\n";
        }
    } else {
        echo "   - Admin user found: NO\n";
    }
} catch (Exception $e) {
    echo "   - MoviePolicy: ERROR - " . $e->getMessage() . "\n";
}

echo "\n6. Authorization Gate Check:\n";
try {
    $gate = $app->make('Illuminate\Auth\Access\Gate');
    echo "   - Gate resolved: YES\n";
} catch (Exception $e) {
    echo "   - Gate: ERROR - " . $e->getMessage() . "\n";
}

echo "\n7. Route Check:\n";
try {
    $router = $app->make('Illuminate\Routing\Router');
    $routes = $router->getRoutes();
    $sourceRoute = null;
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'admin/movies/{movie}/sources') && $route->methods()[0] === 'POST') {
            $sourceRoute = $route;
            break;
        }
    }

    if ($sourceRoute) {
        echo "   - Sources POST route found: YES\n";
        echo "   - Route URI: " . $sourceRoute->uri() . "\n";
        echo "   - Route action: " . $sourceRoute->getActionName() . "\n";
    } else {
        echo "   - Sources POST route found: NO\n";
    }
} catch (Exception $e) {
    echo "   - Route check: ERROR - " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";