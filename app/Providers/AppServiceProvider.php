<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Fix Livewire saat app diakses via subfolder Laragon (localhost/TA/public/)
        $appUrl = config('app.url');
        $urlPath = parse_url($appUrl, PHP_URL_PATH);

        if ($urlPath && $urlPath !== '/') {
            $base = rtrim($urlPath, '/');

            // Fix URL script livewire.js
            \Illuminate\Support\Facades\Config::set(
                'livewire.asset_url',
                $base . '/livewire/livewire.js'
            );

            // Fix endpoint update Livewire AJAX
            Livewire::setUpdateRoute(function ($handle) use ($base) {
                return Route::post($base . '/livewire/update', $handle)
                    ->middleware('web');
            });
        }
    }
}
