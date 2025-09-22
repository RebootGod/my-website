<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Series;
use Illuminate\Support\Facades\Http;

class SeriesImportTMDBController extends Controller
{
    public function showForm()
    {
        return view('admin.series.import_tmdb');
    }

    public function import(Request $request)
    {
        $request->validate([
            'tmdb_id' => 'required|integer',
        ]);

        $tmdbId = $request->input('tmdb_id');
        $apiKey = config('services.tmdb.api_key');
        $response = Http::get("https://api.themoviedb.org/3/tv/{$tmdbId}", [
            'api_key' => $apiKey,
            'language' => 'en-US',
        ]);

        if ($response->failed()) {
            return back()->withErrors(['tmdb_id' => 'Gagal mengambil data dari TMDB.']);
        }

        $data = $response->json();
        $series = Series::create([
            'title' => $data['name'] ?? 'Unknown',
            'description' => $data['overview'] ?? null,
        ]);

        return redirect()->route('admin.series.index')->with('success', 'Series berhasil diimport dari TMDB!');
    }
}
