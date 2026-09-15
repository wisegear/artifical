<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthorProfileRequest;
use App\Models\AuthorProfile;
use App\Services\AuthorImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthorProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.author', ['profile' => AuthorProfile::findOrFail(1)]);
    }

    public function update(AuthorProfileRequest $request, AuthorImages $images): RedirectResponse
    {
        $profile = AuthorProfile::findOrFail(1);
        $data = $request->safe()->only(['name', 'bio', 'x_url', 'facebook_url', 'linkedin_url']);
        $oldImage = $profile->image;
        $newImage = null;

        if ($request->hasFile('image')) {
            $newImage = $images->store($request->file('image'));
            $data['image'] = $newImage;
        }

        try {
            $profile->update($data);
        } catch (\Throwable $exception) {
            if ($newImage) {
                $images->delete($newImage);
            }

            throw $exception;
        }

        if ($newImage && $oldImage) {
            $images->delete($oldImage);
        }

        return redirect()->route('admin.author.edit')->with('status', 'Author profile saved.');
    }
}
