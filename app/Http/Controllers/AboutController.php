<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function show(): View
    {
        return view('about', ['page' => Page::where('slug', 'about')->firstOrFail()]);
    }
}
