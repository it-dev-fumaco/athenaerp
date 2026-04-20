<?php

namespace App\Providers;

use App\Services\CutoffDateService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CutoffDateService::class, fn () => new CutoffDateService);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('pagination::bootstrap-4');
    }
}
