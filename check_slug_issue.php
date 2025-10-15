<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check for movies with problematic slugs
$movies = App\Models\Movie::whereIn('slug', ['-2014', '-2015'])
    ->get(['id', 'title', 'slug', 'year', 'tmdb_id']);

echo "Movies with problematic slugs:\n";
echo "================================\n";
foreach ($movies as $movie) {
    echo "ID: {$movie->id}\n";
    echo "Title: {$movie->title}\n";
    echo "Slug: {$movie->slug}\n";
    echo "Year: {$movie->year}\n";
    echo "TMDB ID: {$movie->tmdb_id}\n";
    echo "--------------------------------\n";
}

// Check for more edge cases - slugs that start with dash
$dashMovies = App\Models\Movie::where('slug', 'like', '-%')->get(['id', 'title', 'slug', 'year']);
echo "\nMovies with slugs starting with dash:\n";
echo "================================\n";
foreach ($dashMovies as $movie) {
    echo "ID: {$movie->id} | Title: {$movie->title} | Slug: {$movie->slug} | Year: {$movie->year}\n";
}
