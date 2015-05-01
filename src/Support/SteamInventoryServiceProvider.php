<?php namespace Braseidon\SteamInventory\Support;

use Braseidon\SteamInventory\Inventory;
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
        //
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../../config/braseidon.steaminventory.php';
        $this->mergeConfigFrom($configPath, 'braseidon.steaminventory');
        $this->publishes([$configPath => config_path('braseidon.steaminventory.php')], 'config');

        // UserRepository
        $this->app->bindShared('braseidon.steaminventory', function ($app) {
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
