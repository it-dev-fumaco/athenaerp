<?php

namespace App\Providers;

use App\Models\Item;
use App\Observers\ItemObserver;
use App\Services\CutoffDateService;
use App\Services\Typesense\TypesenseCollectionManager;
use App\Services\Typesense\TypesenseItemIndexer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Typesense\Client;

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

        $this->app->singleton(Client::class, function () {
            return new Client(config('typesense.client'));
        });

        $this->app->singleton(TypesenseCollectionManager::class);
        $this->app->singleton(TypesenseItemIndexer::class);
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

        Item::observe(ItemObserver::class);
    }
}
