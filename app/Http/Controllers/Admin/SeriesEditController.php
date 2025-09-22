<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Series;

class SeriesEditController extends Controller
{
    public function showForm($id)
    {
        $series = Series::findOrFail($id);
        return view('admin.series.edit', compact('series'));
    }

    public function update(Request $request, $id)
    {
        $series = Series::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $series->update($validated);
        return redirect()->route('admin.series.index')->with('success', 'Series berhasil diupdate!');
    }
}
