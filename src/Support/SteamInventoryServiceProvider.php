<?php namespace Braseidon\SteamInventory\Support;

use Illuminate\Support\ServiceProvider;

class SteamInventoryServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    // protected $defer = true;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('braseidon/steam-inventory-laravel');
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        // UserRepository
        $this->app->bindShared('Braseidon\SteamInventory\Inventory', function ($app) {
            return new Inventory();
        });

        $this->app->alias('braseidon.steaminventory', 'Braseidon\SteamInventory\Inventory');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['braseidon.steaminventory'];
    }
}
