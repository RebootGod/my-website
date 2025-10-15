<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Str;

// Fix the problematic movies
$movies = App\Models\Movie::whereIn('id', [499, 500])->get();

echo "Fixing movie slugs:\n";
echo "================================\n";

foreach ($movies as $movie) {
    $oldSlug = $movie->slug;
    
    // Generate correct slug
    $newSlug = Str::slug($movie->title);
    if ($movie->year) {
        $newSlug .= '-' . $movie->year;
    }
    
    // Ensure uniqueness
    $counter = 1;
    $testSlug = $newSlug;
    while (App\Models\Movie::where('slug', $testSlug)->where('id', '!=', $movie->id)->exists()) {
        $testSlug = $newSlug . '-' . $counter;
        $counter++;
    }
    $newSlug = $testSlug;
    
    // Update
    $movie->slug = $newSlug;
    $movie->save();
    
    echo "Movie ID: {$movie->id}\n";
    echo "Title: {$movie->title}\n";
    echo "Old Slug: '{$oldSlug}'\n";
    echo "New Slug: '{$newSlug}'\n";
    echo "✓ Updated successfully!\n";
    echo "--------------------------------\n";
}

echo "\nVerification:\n";
echo "================================\n";
$updated = App\Models\Movie::whereIn('id', [499, 500])->get(['id', 'title', 'slug']);
foreach ($updated as $movie) {
    echo "ID {$movie->id}: {$movie->title} => {$movie->slug}\n";
}
