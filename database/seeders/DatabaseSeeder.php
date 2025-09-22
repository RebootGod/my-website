<?php
// ========================================
// DATABASE SEEDER
// ========================================
// File: database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\InviteCode;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create Super Admin
        $superAdmin = User::create([
            'username' => 'admin',
            'email' => 'admin@noobzmovie.com',
            'password' => Hash::make('admin123'),
            'role' => 'super_admin',
            'status' => 'active'
        ]);

        // Create Test Users
        $user1 = User::create([
            'username' => 'testuser1',
            'email' => 'user1@test.com',
            'password' => Hash::make('password123'),
            'role' => 'member',
            'status' => 'active'
        ]);

        $user2 = User::create([
            'username' => 'testuser2',
            'email' => 'user2@test.com', 
            'password' => Hash::make('password123'),
            'role' => 'member',
            'status' => 'active'
        ]);

        // Create Invite Codes
        InviteCode::create([
            'code' => 'WELCOME2024',
            'description' => 'Welcome code for new users',
            'status' => 'active',
            'used_count' => 0,
            'max_uses' => null, // Unlimited
            'created_by' => $superAdmin->id
        ]);

        InviteCode::create([
            'code' => 'VIP50',
            'description' => 'Limited VIP code',
            'status' => 'active',
            'used_count' => 0,
            'max_uses' => 50,
            'created_by' => $superAdmin->id
        ]);

        InviteCode::create([
            'code' => 'EXPIRED',
            'description' => 'Expired code for testing',
            'status' => 'inactive',
            'used_count' => 10,
            'max_uses' => 10,
            'created_by' => $superAdmin->id
        ]);

        // Create Genres (Based on TMDB)
        $genres = [
            ['tmdb_id' => 28, 'name' => 'Action', 'slug' => 'action'],
            ['tmdb_id' => 12, 'name' => 'Adventure', 'slug' => 'adventure'],
            ['tmdb_id' => 16, 'name' => 'Animation', 'slug' => 'animation'],
            ['tmdb_id' => 35, 'name' => 'Comedy', 'slug' => 'comedy'],
            ['tmdb_id' => 80, 'name' => 'Crime', 'slug' => 'crime'],
            ['tmdb_id' => 99, 'name' => 'Documentary', 'slug' => 'documentary'],
            ['tmdb_id' => 18, 'name' => 'Drama', 'slug' => 'drama'],
            ['tmdb_id' => 10751, 'name' => 'Family', 'slug' => 'family'],
            ['tmdb_id' => 14, 'name' => 'Fantasy', 'slug' => 'fantasy'],
            ['tmdb_id' => 36, 'name' => 'History', 'slug' => 'history'],
            ['tmdb_id' => 27, 'name' => 'Horror', 'slug' => 'horror'],
            ['tmdb_id' => 10402, 'name' => 'Music', 'slug' => 'music'],
            ['tmdb_id' => 9648, 'name' => 'Mystery', 'slug' => 'mystery'],
            ['tmdb_id' => 10749, 'name' => 'Romance', 'slug' => 'romance'],
            ['tmdb_id' => 878, 'name' => 'Science Fiction', 'slug' => 'science-fiction'],
            ['tmdb_id' => 10770, 'name' => 'TV Movie', 'slug' => 'tv-movie'],
            ['tmdb_id' => 53, 'name' => 'Thriller', 'slug' => 'thriller'],
            ['tmdb_id' => 10752, 'name' => 'War', 'slug' => 'war'],
            ['tmdb_id' => 37, 'name' => 'Western', 'slug' => 'western']
        ];

        foreach ($genres as $genre) {
            Genre::create($genre);
        }

        // Create Sample Movies
        $movies = [
            [
                'title' => 'Avengers: Endgame',
                'description' => 'After the devastating events of Avengers: Infinity War, the universe is in ruins. With the help of remaining allies, the Avengers assemble once more in order to reverse Thanos actions and restore balance to the universe.',
                'embed_url' => 'https://short.icu/example1',
                'poster_path' => 'https://image.tmdb.org/t/p/w500/or06FN3Dka5tukK1e9sl16pB3iy.jpg',
                'backdrop_path' => 'https://image.tmdb.org/t/p/original/7RyHsO4yDXtBv1zUU3mTpHeQ0d5.jpg',
                'year' => 2019,
                'duration' => 181,
                'rating' => 8.4,
                'quality' => 'FHD',
                'status' => 'published',
                'tmdb_id' => 299534,
                'genres' => ['action', 'adventure', 'science-fiction']
            ],
            [
                'title' => 'Spider-Man: No Way Home',
                'description' => 'Peter Parker is unmasked and no longer able to separate his normal life from the high-stakes of being a super-hero. When he asks for help from Doctor Strange the stakes become even more dangerous.',
                'embed_url' => 'https://short.icu/example2',
                'poster_path' => 'https://image.tmdb.org/t/p/w500/1g0dhYtq4irTY1GPXvft6k4YLjm.jpg',
                'backdrop_path' => 'https://image.tmdb.org/t/p/original/iQFcwSGbZXMkeyKrxbPnwnRo5fl.jpg',
                'year' => 2021,
                'duration' => 148,
                'rating' => 8.3,
                'quality' => 'FHD',
                'status' => 'published',
                'tmdb_id' => 634649,
                'genres' => ['action', 'adventure', 'science-fiction']
            ],
            [
                'title' => 'The Batman',
                'description' => 'In his second year of fighting crime, Batman uncovers corruption in Gotham City that connects to his own family while facing a serial killer known as the Riddler.',
                'embed_url' => 'https://short.icu/example3',
                'poster_path' => 'https://image.tmdb.org/t/p/w500/74xTEgt7R36Fpooo50r9T25onhq.jpg',
                'backdrop_path' => 'https://image.tmdb.org/t/p/original/b0PlSFdDwbyK0cf5RxwDpaOJQvQ.jpg',
                'year' => 2022,
                'duration' => 176,
                'rating' => 7.9,
                'quality' => 'FHD',
                'status' => 'published',
                'tmdb_id' => 414906,
                'genres' => ['crime', 'mystery', 'thriller']
            ],
            [
                'title' => 'Dune',
                'description' => 'Paul Atreides, a brilliant and gifted young man born into a great destiny beyond his understanding, must travel to the most dangerous planet in the universe.',
                'embed_url' => 'https://short.icu/example4',
                'poster_path' => 'https://image.tmdb.org/t/p/w500/d5NXSklXo0qyIYkgV94XAgMIckC.jpg',
                'backdrop_path' => 'https://image.tmdb.org/t/p/original/jYEW5xZkZk2WTrdbMGAPFuBqbDc.jpg',
                'year' => 2021,
                'duration' => 155,
                'rating' => 8.0,
                'quality' => '4K',
                'status' => 'published',
                'tmdb_id' => 438631,
                'genres' => ['science-fiction', 'adventure']
            ],
            [
                'title' => 'Top Gun: Maverick',
                'description' => 'After more than thirty years of service as one of the Navy\'s top aviators, Pete Mitchell is where he belongs, pushing the envelope as a courageous test pilot.',
                'embed_url' => 'https://short.icu/example5',
                'poster_path' => 'https://image.tmdb.org/t/p/w500/62HCnUTziyWcpDaBO2i1DX17ljH.jpg',
                'backdrop_path' => 'https://image.tmdb.org/t/p/original/AaV1YIdWKnjAIAOe8UUKBFm327v.jpg',
                'year' => 2022,
                'duration' => 130,
                'rating' => 8.3,
                'quality' => 'FHD',
                'status' => 'published',
                'tmdb_id' => 361743,
                'genres' => ['action', 'drama']
            ]
        ];

        foreach ($movies as $movieData) {
            $genreSlugs = $movieData['genres'];
            unset($movieData['genres']);
            
            $movieData['added_by'] = $superAdmin->id;
            $movieData['view_count'] = rand(100, 5000);
            
            $movie = Movie::create($movieData);
            
            // Attach genres
            $genreIds = Genre::whereIn('slug', $genreSlugs)->pluck('id');
            $movie->genres()->attach($genreIds);
        }

        // Create more sample movies for pagination testing
        for ($i = 1; $i <= 20; $i++) {
            $movie = Movie::create([
                'title' => "Sample Movie {$i}",
                'slug' => "sample-movie-{$i}",
                'description' => "This is a sample movie description for testing purposes. Movie number {$i}.",
                'embed_url' => "https://short.icu/sample{$i}",
                'poster_path' => 'https://placehold.co/300x450/333/fff?text=Movie+' . $i,
                'backdrop_path' => 'https://placehold.co/1920x1080/333/fff?text=Backdrop+' . $i,
                'year' => rand(2018, 2024),
                'duration' => rand(90, 180),
                'rating' => rand(50, 95) / 10,
                'quality' => ['HD', 'FHD', '4K'][rand(0, 2)],
                'status' => 'published',
                'added_by' => $superAdmin->id,
                'view_count' => rand(10, 1000)
            ]);

            // Attach random genres
            $randomGenres = Genre::inRandomOrder()->take(rand(2, 4))->pluck('id');
            $movie->genres()->attach($randomGenres);
        }

        $this->command->info('Seeding completed successfully!');
        $this->command->info('');
        $this->command->info('=== LOGIN CREDENTIALS ===');
        $this->command->info('Admin: admin@noobzmovie.com / admin123');
        $this->command->info('User: user1@test.com / password123');
        $this->command->info('');
        $this->command->info('=== INVITE CODES ===');
        $this->command->info('Active: WELCOME2024 (unlimited)');
        $this->command->info('Active: VIP50 (50 uses max)');
        $this->command->info('Inactive: EXPIRED (for testing)');
    }
}