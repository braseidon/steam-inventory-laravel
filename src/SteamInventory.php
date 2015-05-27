<?php namespace Braseidon\SteamInventory;

use Config;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;

use InvalidArgumentException;

class SteamInventory
{

    /**
     * @var CacheManager $cache Caching layer
     */
    protected $cache;

    /**
     * @var Collection $collection The Collection instance
     */
    protected $collection;

    /**
     * @var integer $cacheTime Number of minutes to cache a Steam ID's inventory
     */
    protected $cacheTime = 30;

    /**
     * @var string $cacheTag The Cache tag that will be used for all items
     */
    protected $cacheTag = 'steam.inventory';

    /**
     * @var mixed The last inventory that was pulled
     */
    protected $currentData;

    /**
     * @param string $cache Instantiate the Object
     */
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
        $this->collection = new Collection();
    }

    /**
     * Load the inventory for a Steam ID
     *
     * @param  integer $steamId
     * @return Json
     */
    public function loadInventory($steamId, $appId = 730, $contextId = 2)
    {
        if ($this->cache->tags($this->cacheTag)->has($steamId)) {
            $this->currentData = $this->cache->tags($this->cacheTag)->get($steamId);
        }

        $inventory = $this->getSteamInventory($steamId, $appId, $contextId);

        if (is_array($inventory)) {
            $minutes = Config::get('braseidon.steam-inventory.cache_time');
            $this->cache->tags($this->cacheTag)->put($steamId, $inventory, $minutes);

            $this->currentData = $inventory;
        } else {
            return false;
        }

        return $this;
    }

    /**
     * Fetches the inventory of the Steam ID
     *
     * @param  integer $steamId
     * @param  integer $appId
     * @param  integer $contextId
     * @return Json
     */
    private function getSteamInventory($steamId, $appId, $contextId)
    {
        $steamId = $this->cleanSteamId($steamId);

        $this->checkInfo($steamId, $appId, $contextId);

        $url  = $this->steamApiUrl($steamId, $appId, $contextId);
        $json = json_decode(file_get_contents($url), true);

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
    | Returning Data
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Returns the current Inventory data
     *
     * @return Collection
     */
    public function getInventory()
    {
        if (! $this->currentData) {
            return false;
        }

        $data = array_get($this->currentData, 'rgInventory');

        return $this->collection->make($data);
    }

    /**
     * Returns the current Inventory Description data
     *
     * @return Collection
     */
    public function getDescriptions()
    {
        if (! $this->currentData) {
            return false;
        }

        $data = array_get($this->currentData, 'rgDescriptions', false);
        $data = $this->collection->make($data);
        // dd($data);

        $items = $this->parseItemDescriptions($data);

        return $items;
    }

    /*
    |--------------------------------------------------------------------------
    | Description Builder
    |--------------------------------------------------------------------------
    |
    |
    */

    public function parseItemDescriptions(Collection $data)
    {
        $items = $this->collection->make();

        if ($data->count() == 0) {
            return $items;
        }

        foreach ($data as $dataItem) {
            // Ignore untradable items
            if (array_get($dataItem, 'tradable') !== 1 || array_get($dataItem, 'instanceid') == 0) {
                continue;
            }

            $name = trim(last(explode('|', array_get($dataItem, 'name'))));
            $desc = $this->parseItemDescription(array_get($dataItem, 'descriptions'));
            $tags = $this->parseItemTags(array_get($dataItem, 'tags'));
            $cat  = array_get($tags, 'Category', '');

            $array = [
                    'appid'           => array_get($dataItem, 'appid'),            // 730
                    'classid'         => array_get($dataItem, 'classid'),          // 310777928
                    'instanceid'      => array_get($dataItem, 'instanceid'),       // 480085569 or 0
                    'name'            => $name,                                     // Sand Dune
                    'market_name'     => array_get($dataItem, 'market_name'),      // P250 | Sand Dune (Field-Tested)
                    'weapon'          => array_get($tags, 'Weapon'),                // P250
                    'type'            => array_get($tags, 'Type'),                  // Pistol
                    'quality'         => array_get($tags, 'Quality'),               // Consumer Grade
                    'exterior'        => array_get($tags, 'Exterior'),              // Field-Tested
                    'collection'      => array_get($tags, 'Collection'),            // The Dust 2 Collection
                    'stattrack'       => (stripos($cat, 'StatTrak') !== false) ? true : false,
                    'icon_url'        => array_get($dataItem, 'icon_url'),         // fWFc82js0fmoRAP-qOIPu5THSWqfSmTEL ...
                    'icon_url_large'  => array_get($dataItem, 'icon_url_large'),   // fWFc82js0fmoRAP-qOIPu5THSWqfSmTEL ...
                    'description'     => $desc,
                    'name_color'      => '#' . array_get($dataItem, 'name_color'),
                ];

            $items->push(json_decode(json_encode($array)));

            unset($desc, $tags, $cat, $array);
        }

        return $items;
    }

    /**
     * Parse an item's description about the item
     *
     * @param  StdClass $description
     * @return string
     */
    protected function parseItemDescription($description)
    {
        $description = json_decode(json_encode($description), true);

        return trim(array_get($description, '2.value'));
    }

    /**
     * Parses an item's tags into a usable array
     *
     * @param  array  $tags
     * @return array
     */
    protected function parseItemTags(array $tags)
    {
        if (! count($tags)) {
            return [];
        }

        $parsed = [];

        foreach ($tags as $tag) {
            $categoryName = array_get($tag, 'category_name');
            $tagName = array_get($tag, 'name');

            $parsed[$categoryName] = $tagName;
        }

        return $parsed;
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
