<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use Illuminate\Http\Request;

class SeriesListController extends Controller
{
    public function __invoke(Request $request)
    {
        $series = Series::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.series.index', compact('series'));
    }
}
