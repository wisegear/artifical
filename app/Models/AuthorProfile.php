<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'bio', 'image', 'x_url', 'facebook_url', 'linkedin_url'])]
class AuthorProfile extends Model
{
    use HasFactory;
}
