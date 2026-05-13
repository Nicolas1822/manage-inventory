<?php

namespace App\Providers;

use App\Models\Producto;
use App\Models\Factura;
use App\Observers\ProductoObserver;
use App\Observers\FacturaObserver;
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
    Producto::observe(ProductoObserver::class);
    Factura::observe(FacturaObserver::class);
  }
}
