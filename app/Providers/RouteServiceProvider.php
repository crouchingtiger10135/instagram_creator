<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        // You can set up route model bindings or pattern filters here.
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        // Example: load your "web" routes
        Route::middleware('web')
            ->group(base_path('routes/web.php'));

        // Example: load your "api" routes
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('routes/api.php'));
    }
}
