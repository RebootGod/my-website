<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get complete data for these movies
$movies = App\Models\Movie::whereIn('id', [499, 500])->get();

echo "Complete movie data:\n";
echo "================================\n";
foreach ($movies as $movie) {
    echo "ID: {$movie->id}\n";
    echo "Title: '{$movie->title}'\n";
    echo "Slug: '{$movie->slug}'\n";
    echo "Year: {$movie->year}\n";
    echo "TMDB ID: {$movie->tmdb_id}\n";
    echo "IMDB ID: {$movie->imdb_id}\n";
    echo "Created: {$movie->created_at}\n";
    echo "Updated: {$movie->updated_at}\n";
    echo "Status: {$movie->status}\n";
    echo "================================\n\n";
}

// Test regenerating correct slug
use Illuminate\Support\Str;

echo "\nProposed slug fixes:\n";
echo "================================\n";
foreach ($movies as $movie) {
    $correctSlug = Str::slug($movie->title);
    if ($movie->year) {
        $correctSlug .= '-' . $movie->year;
    }
    
    // Check if slug exists
    $exists = App\Models\Movie::where('slug', $correctSlug)
        ->where('id', '!=', $movie->id)
        ->exists();
    
    echo "Movie ID: {$movie->id}\n";
    echo "Current Title: '{$movie->title}'\n";
    echo "Current Slug: '{$movie->slug}'\n";
    echo "Proposed Slug: '{$correctSlug}'\n";
    echo "Slug exists: " . ($exists ? 'YES - need uniqueness handling' : 'NO - safe to use') . "\n";
    echo "--------------------------------\n";
}
