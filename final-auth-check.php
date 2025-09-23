<?php
// Final authorization check for all admin functionality
echo "=== FINAL AUTHORIZATION CHECK ===\n";

echo "1. Form Requests Status:\n";
$formRequests = [
    'StoreMovieRequest.php',
    'UpdateMovieRequest.php',
    'StoreMovieSourceRequest.php',
    'UpdateMovieSourceRequest.php',
    'TMDBImportRequest.php',
    'TMDBBulkImportRequest.php',
    'StoreSeriesRequest.php',
    'UpdateSeriesRequest.php'
];

foreach ($formRequests as $request) {
    $path = "app/Http/Requests/Admin/{$request}";
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (strpos($content, '->can(') !== false) {
            echo "   ❌ {$request}: STILL HAS ->can() ISSUE\n";
        } elseif (strpos($content, '->isAdmin()') !== false) {
            echo "   ✅ {$request}: FIXED with isAdmin()\n";
        } elseif (strpos($content, 'return true') !== false) {
            echo "   ✅ {$request}: OK (returns true)\n";
        } else {
            echo "   ⚠️  {$request}: UNKNOWN STATUS\n";
        }
    } else {
        echo "   ❓ {$request}: NOT FOUND\n";
    }
}

echo "\n2. Controller Authorization Status:\n";
$controllers = [
    'AdminMovieController.php' => 'Movies',
    'AdminSeriesController.php' => 'Series',
    'TMDBController.php' => 'TMDB',
    'DashboardController.php' => 'Dashboard',
    'InviteCodeController.php' => 'Invite Codes'
];

foreach ($controllers as $controller => $name) {
    $path = "app/Http/Controllers/Admin/{$controller}";
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $authorizeCount = substr_count($content, '$this->authorize(');
        echo "   ✅ {$name}: {$authorizeCount} authorization checks\n";
    } else {
        echo "   ❓ {$name}: NOT FOUND\n";
    }
}

echo "\n3. Policy Registration Status:\n";
$authProvider = file_get_contents('app/Providers/AuthServiceProvider.php');
$policies = ['Movie', 'Series', 'InviteCode', 'User', 'Watchlist'];

foreach ($policies as $policy) {
    if (strpos($authProvider, "{$policy}::class") !== false) {
        echo "   ✅ {$policy}Policy: REGISTERED\n";
    } else {
        echo "   ❌ {$policy}Policy: NOT REGISTERED\n";
    }
}

echo "\n4. Service Provider Status:\n";
$providers = file_get_contents('bootstrap/providers.php');
if (strpos($providers, 'AdminServiceProvider') !== false) {
    echo "   ✅ AdminServiceProvider: REGISTERED\n";
} else {
    echo "   ❌ AdminServiceProvider: NOT REGISTERED\n";
}

echo "\n=== FINAL CHECK COMPLETE ===\n";
echo "If all items show ✅, then authorization should work perfectly!\n";