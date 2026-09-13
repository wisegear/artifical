<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class BlogImages
{
    public function store(UploadedFile $file): string
    {
        $image = ImageManager::usingDriver(Driver::class)->decodePath($file->getPathname())->scaleDown(width: 1600, height: 1600);
        $path = 'posts/'.Str::uuid().'.webp';
        if (! Storage::disk('public')->put($path, (string) $image->encodeUsingFormat(Format::WEBP, quality: 82))) {
            throw new \RuntimeException('The image could not be stored.');
        }

        return $path;
    }
}
