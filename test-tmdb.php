<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing TMDB API connection...\n";
echo "API Key: " . config('services.tmdb.api_key') . "\n";

try {
    $service = new App\Services\NewTMDBService();
    echo "Service created successfully\n";
    $result = $service->search('1074313');
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
