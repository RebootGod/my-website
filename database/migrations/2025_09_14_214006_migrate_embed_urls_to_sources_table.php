<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Movie;
use App\Models\MovieSource;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate all existing movies.embed_url to movie_sources table
        $movies = Movie::whereNotNull('embed_url')->get();
        
        foreach ($movies as $movie) {
            if (!$movie->embed_url) {
                continue;
            }
            
            // Get decrypted embed URL
            try {
                $decryptedUrl = decrypt($movie->embed_url);
            } catch (\Exception $e) {
                // If decryption fails, use as-is (might be plain text)
                $decryptedUrl = $movie->embed_url;
            }
            
            // Check if this movie already has a primary source
            $existingPrimary = MovieSource::where('movie_id', $movie->id)
                ->where('source_name', 'like', 'Server 1%')
                ->orWhere('priority', 100)
                ->first();
            
            if (!$existingPrimary) {
                // Create primary source from main embed URL
                MovieSource::create([
                    'movie_id' => $movie->id,
                    'source_name' => 'Server 1 (Primary)',
                    'embed_url' => $decryptedUrl,
                    'quality' => $movie->quality ?? 'HD',
                    'priority' => 100, // Highest priority for primary source
                    'is_active' => true,
                ]);
                
                echo "Migrated embed URL for movie ID {$movie->id}: {$movie->title}\n";
            }
        }
        
        // After migration, clear the embed_url field
        DB::table('movies')->update(['embed_url' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migrated primary sources and restore embed_url to movies
        $primarySources = MovieSource::where('source_name', 'like', 'Server 1 (Primary)')
            ->orWhere('priority', 100)
            ->get();
            
        foreach ($primarySources as $source) {
            // Restore embed_url to movie
            $movie = Movie::find($source->movie_id);
            if ($movie) {
                $movie->update(['embed_url' => $source->embed_url]);
                echo "Restored embed URL for movie ID {$movie->id}: {$movie->title}\n";
            }
            
            // Delete the migrated source
            $source->delete();
        }
    }
};
