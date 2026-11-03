<?php

namespace App\Providers;

use App\Models\Factura;
use App\Models\Producto;
use App\Observers\FacturaObserver;
use App\Observers\ProductoObserver;
use App\Services\NegocioActual;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(NegocioActual::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/alter'));

        Producto::observe(ProductoObserver::class);
        Factura::observe(FacturaObserver::class);
    }
}
