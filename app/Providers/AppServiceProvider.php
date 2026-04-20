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
        Paginator::defaultView('pagination::bootstrap-4');

        $appUrl = (string) config('app.url', '');
        if (config('app.force_https') || str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
