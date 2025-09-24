<?php
// Debug movie sources embed URLs
echo "=== DEBUGGING MOVIE SOURCES ===\n";

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get the movie - try different slug variations
$movie = App\Models\Movie::where('slug', 'malajumat-the-movie')->first();
if (!$movie) {
    $movie = App\Models\Movie::where('slug', 'malamjumat-the-movie')->first();
}
if (!$movie) {
    $movie = App\Models\Movie::where('title', 'like', '%Malam Jumat%')->first();
}

if (!$movie) {
    echo "❌ Movie 'malajumat-the-movie' not found\n";
    exit;
}

echo "✅ Movie found: {$movie->title}\n";
echo "   - Movie ID: {$movie->id}\n";
echo "   - Movie embed_url: " . ($movie->embed_url ?: 'NULL') . "\n";

// Get all sources
$sources = $movie->sources()->get();

echo "\n📹 SOURCES:\n";
foreach ($sources as $source) {
    echo "   Source ID: {$source->id}\n";
    echo "   Name: {$source->source_name}\n";
    echo "   Quality: {$source->quality}\n";
    echo "   Priority: {$source->priority}\n";
    echo "   Active: " . ($source->is_active ? 'YES' : 'NO') . "\n";
    echo "   Embed URL: " . ($source->embed_url ?: 'NULL') . "\n";
    echo "   ---\n";
}

echo "\n=== DEBUG COMPLETE ===\n";