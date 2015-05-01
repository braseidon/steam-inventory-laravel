<?php namespace Braseidon\SteamInventory\Support;

use Illuminate\Support\Facades\Facade;

class SteamInventory extends Facade
{

    /**
    * Get the registered name of the component.
    *
    * @return string
    */
    protected static function getFacadeAccessor()
    {
        return 'braseidon.steaminventory';
    }
}
