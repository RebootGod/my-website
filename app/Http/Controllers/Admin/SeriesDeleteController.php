<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use Illuminate\Http\Request;

class SeriesDeleteController extends Controller
{
    public function destroy(Request $request, $id)
    {
        $series = Series::findOrFail($id);
        $series->delete();
        return redirect()->route('admin.series.index')->with('success', 'Series berhasil dihapus!');
    }
}
