<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'], 'post_date' => ['required', 'date_format:d/m/Y'],
            'seo_summary' => ['required', 'string', 'max:255'], 'category' => ['required', 'string', 'max:80'],
            'tags' => ['nullable', 'string', 'max:500'], 'body' => ['required', 'string', 'max:200000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=6000,max_height=6000'],
            'is_featured' => ['sometimes', 'boolean'], 'is_subscriber' => ['sometimes', 'boolean'],
            'action' => ['required', 'in:draft,publish'],
        ];
    }
}
