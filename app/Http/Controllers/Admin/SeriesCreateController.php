<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Series;

class SeriesCreateController extends Controller
{
    public function showForm()
    {
        return view('admin.series.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $series = Series::create($validated);

        return redirect()->route('admin.series.index')->with('success', 'Series berhasil ditambahkan!');
    }
}
