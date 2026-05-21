<?php

namespace App\Providers;

use App\Models\CarreraUsuario;
use App\Observers\CarreraUsuarioObserver;
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
        CarreraUsuario::observe(CarreraUsuarioObserver::class);
    }
}
