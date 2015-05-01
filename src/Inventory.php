<?php namespace Braseidon\SteamInventory;

use Cache;
use Config;
use InvalidArgumentException;

class Inventory
{

    /**
     * Caching layer
     *
     * @var $cache
     */
    protected $cache;

    /**
     * Number of minutes to cache a Steam ID's inventory
     *
     * @var integer
     */
    protected $cacheTime = 30;

    /**
     * Instantiate the Object
     *
     * @param string $cache
     */
    public function __construct($cache = '')
    {
        $this->cache = $cache;

    }

    /**
     * Load the inventory for a Steam ID
     *
     * @param  integer $steamId
     * @return Json
     */
    public static function load($steamId, $appId = 730, $contextId = 2)
    {
        if (Cache::has($steamId)) {
            return Cache::get($steamId);
        }

        $obj = new static;

        $inventory = $obj->getApiJson($steamId, $appId, $contextId);

        if ($inventory !== null) {
            $minutes = Config::get('braseidon.steaminventory.cache_time');
            Cache::put($steamId, $inventory, $minutes);

            return $inventory;
        }

        return $inventory;
    }

    /**
     * Fetches the inventory of the Steam ID
     *
     * @param  integer $steamId
     * @param  integer $appId
     * @param  integer $contextId
     * @return Json
     */
    public function getApiJson($steamId, $appId, $contextId)
    {
        $steamId = $this->cleanSteamId($steamId);

        $this->checkInfo($steamId, $appId, $contextId);

        $url  = $this->steamApiUrl($steamId, $appId, $contextId);
        $json = json_decode(file_get_contents($url));

        return $json;
    }

    /**
     * Returns a formatted Steam API Url
     *
     * @param  integer $steamId
     * @param  integer $appId
     * @param  integer $contextId
     * @return string
     */
    protected function steamApiUrl($steamId, $appId, $contextId)
    {
        return 'http://steamcommunity.com/profiles/' . $steamId . '/inventory/json/' . $appId . '/' . $contextId . '/';
    }

    /**
     * Checks if all the variables are numbers
     *
     * @param  integer $steamId
     * @param  integer $appId
     * @param  integer $contextId
     * @return bool
     */
    protected function checkInfo($steamId, $appId, $contextId)
    {
        if (! is_numeric($steamId) || ! is_numeric($appId) || ! is_numeric($contextId)) {
            throw new InvalidArgumentException('One or more variables are invalid: `steamId`, `appId`, `contextId`. They must be numeric!');

            return false;
        }

        return true;
    }

    /**
     * Prepares the Steam ID for usage against most copy/paste problems
     *
     * @param  string $steamId
     * @return integer
     */
    protected function cleanSteamId($steamId)
    {
        $steamId = trim($steamId);

        if (is_numeric($steamId)) {
            return $steamId;
        }

        return $this->steamIdTo64($steamId);
    }

    /**
     * Convert a Steam ID to 64 bit, if it isn't already
     *
     * @param  integer $steamId
     * @return integer
     */
    protected function steamIdTo64($steamId)
    {
        if (strlen($steamId) === 17) {
            return $steamId;
        }

        $steamId = explode(':', $steamId);
        $steamId = bcadd((bcadd('76561197960265728', $steamId[1])), (bcmul($steamId[2], '2')));
        $steamId = str_replace('.0000000000', '', $steamId);

        return $steamId64;
    }
}
