<?php

namespace App\Providers;

use App\Models\NavigationItem;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin', fn (User $user): bool => (bool) $user->is_admin);
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        View::composer('layouts.app', function (\Illuminate\View\View $view): void {
            $navigationItems = NavigationItem::query()
                ->whereHas('posts', fn ($query) => $query->published())
                ->with(['posts' => fn ($query) => $query->published()])
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $view->with('navigationItems', $navigationItems);
        });
    }
}
