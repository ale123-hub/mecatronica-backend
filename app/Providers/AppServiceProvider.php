<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(\CloudinaryLabs\CloudinaryLaravel\CloudinaryServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Si necesitas asegurar que la URL se cargue del ENV, hazlo así:
        if (env('CLOUDINARY_URL')) {
            config(['cloudinary.cloudinary_url' => env('CLOUDINARY_URL')]);
        }
    }
}