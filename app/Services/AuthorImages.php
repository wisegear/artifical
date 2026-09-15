<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class AuthorImages
{
    public function store(UploadedFile $file): string
    {
        $path = 'authors/'.Str::uuid().'.webp';
        $image = ImageManager::usingDriver(Driver::class)
            ->decode(file_get_contents($file->getPathname()))
            ->coverDown(600, 600);

        if (! Storage::disk('public')->put($path, (string) $image->encodeUsingFormat(Format::WEBP, quality: 75))) {
            throw new \RuntimeException('The author image could not be stored.');
        }

        return $path;
    }

    public function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    public function delete(string $path): void
    {
        Storage::disk('public')->delete($path);
    }
}
