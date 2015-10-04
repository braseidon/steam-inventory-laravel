<?php
namespace Braseidon\SteamInventory;

use Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

use InvalidArgumentException;

class SteamInventory
{

    /**
     * @var integer $cacheTime Number of minutes to cache a Steam ID's inventory
     */
    protected $cacheTime = 30;

    /**
     * @var string $cacheTag The Cache tag that will be used for all items
     */
    protected $cacheTag = 'steam.inventory';

    /**
     * Load the inventory for a Steam ID
     *
     * @param  integer $steamId
     * @return Json
     */
    public function loadInventory($steamId, $appId = 730, $contextId = 2)
    {
        $rawInventory = Cache::tags([$this->cacheTag])->remember($steamId, $this->cacheTime, function() use ($steamId, $appId, $contextId) {
			return $this->fetchRawSteamInventory($steamId, $appId, $contextId);
		});
        $this->inventory = collect($rawInventory['rgInventory'])
        	// Map the descriptions into the item.
            ->map(function($item) use ($contextId, $rawInventory) {
                $item['contextid'] = $contextId;
                if (!isset($rawInventory['rgDescriptions'][$item['classid'].'_'.$item['instanceid']])) {
                    return $item;
                }
                return array_merge($item, $rawInventory['rgDescriptions'][$item['classid'].'_'.$item['instanceid']]);
            })
            // Map the currencies into the item.
            ->map(function($item) use ($rawInventory) {
                if (!isset($rawInventory['rgCurrency'][$item['classid'].'_'.$item['instanceid']])) {
                    return $item;
                }
                return array_merge($item, $rawInventory['rgCurrency'][$item['classid'].'_'.$item['instanceid']]);
        	});
        return true;
    }

    /**
     * Fetches the inventory of the Steam ID
     *
     * @param  integer $steamId
     * @param  integer $appId
     * @param  integer $contextId
     * @return Json
     */
    private function fetchRawSteamInventory($steamId, $appId, $contextId)
    {
        $steamId = $this->cleanSteamId($steamId);
        $this->checkInfo($steamId, $appId, $contextId);

        $url  = $this->steamApiUrl($steamId, $appId, $contextId);
        $json = json_decode(@file_get_contents($url), true);

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
    private function steamApiUrl($steamId, $appId, $contextId)
    {
        return 'http://steamcommunity.com/profiles/' . $steamId . '/inventory/json/' . $appId . '/' . $contextId . '/';
    }

    /*
    |--------------------------------------------------------------------------
    | Steam ID Cleanup & Check
    |--------------------------------------------------------------------------
    |
    |
    */

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

        return $steamId;
    }
}
