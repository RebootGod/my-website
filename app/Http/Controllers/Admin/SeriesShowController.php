<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;

class SeriesShowController extends Controller
{
    public function show($id)
    {
        $series = Series::findOrFail($id);
        return view('admin.series.show', compact('series'));
    }
}
