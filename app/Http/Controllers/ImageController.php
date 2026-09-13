<?php

namespace App\Http\Controllers;

use App\Services\BlogImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function store(Request $request, BlogImages $images): JsonResponse
    {
        $request->validate(['file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=6000,max_height=6000']]);

        return response()->json(['location' => Storage::disk('public')->url($images->store($request->file('file')))]);
    }
}
