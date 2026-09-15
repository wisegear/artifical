<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NavigationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:80'],
            'posts' => ['required', 'array', 'min:1'],
            'posts.*' => ['integer', 'distinct', 'exists:posts,id'],
        ];
    }
}
