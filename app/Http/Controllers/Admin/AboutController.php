<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AboutRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class AboutController extends Controller
{
    public function edit(): View
    {
        return view('admin.about', ['page' => Page::where('slug', 'about')->firstOrFail()]);
    }

    public function update(AboutRequest $request): RedirectResponse
    {
        $page = Page::where('slug', 'about')->firstOrFail();
        $data = $request->validated();
        $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig)->allowSafeElements()->allowRelativeMedias()->allowRelativeLinks()->allowElement('img', ['src', 'alt', 'width', 'height'])->withMaxInputLength(200000));
        $data['body'] = $sanitizer->sanitize($data['body']);
        $page->update($data);

        return redirect()->route('admin.about.edit')->with('status', 'About page saved.');
    }
}
