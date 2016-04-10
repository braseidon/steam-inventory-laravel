<?php namespace Braseidon\SteamInventory\Support;

use Braseidon\SteamInventory\SteamInventory;
// use Illuminate\Cache\CacheManager;
use Illuminate\Support\ServiceProvider;

class SteamInventoryServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../../config/braseidon.steam-inventory.php';
        $this->mergeConfigFrom($configPath, 'braseidon.steam-inventory');
        $this->publishes([$configPath => config_path('braseidon.steam-inventory.php')], 'config');

        $this->app->bind('braseidon.steam-inventory', function ($app) {
            return new SteamInventory($app->make('Illuminate\Cache\CacheManager'));
        });

        $this->app->alias('braseidon.steam-inventory', 'Braseidon\SteamInventory\Inventory');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['braseidon.steam-inventory'];
    }
}
