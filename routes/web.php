<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\AboutController as AdminAboutController;
use App\Http\Controllers\Admin\AuthorProfileController;
use App\Http\Controllers\Admin\NavigationItemController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BlogController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'show'])->name('about');
Route::get('/posts/{post:slug}', [BlogController::class, 'show'])->name('posts.show');
Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::redirect('/', '/admin/posts');
    Route::post('/images', [ImageController::class, 'store'])->middleware('throttle:30,1')->name('images.store');
    Route::get('/about', [AdminAboutController::class, 'edit'])->name('about.edit');
    Route::put('/about', [AdminAboutController::class, 'update'])->name('about.update');
    Route::get('/author', [AuthorProfileController::class, 'edit'])->name('author.edit');
    Route::put('/author', [AuthorProfileController::class, 'update'])->name('author.update');
    Route::resource('navigation-items', NavigationItemController::class)->except('show');
    Route::get('/posts/{post}/preview', [BlogController::class, 'preview'])->name('posts.preview');
    Route::resource('posts', PostController::class)->except('show');
});
