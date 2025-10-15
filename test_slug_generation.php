<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Str;

$titles = [
    "Parasyte: Part 1",
    "Parasyte: Part 2",
    "Test: Movie",
    "Normal Movie",
    "",
    "   ",
];

echo "Testing Str::slug() behavior:\n";
echo "================================\n";

foreach ($titles as $title) {
    $slug = Str::slug($title);
    echo "Title: '{$title}' => Slug: '{$slug}'\n";
    
    // Test with year
    $slugWithYear = $slug . '-2014';
    echo "  With year: '{$slugWithYear}'\n\n";
}
