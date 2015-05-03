<?php namespace Braseidon\SteamInventory;

use Config;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Inventory
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
    public function load($steamId, $appId = 730, $contextId = 2)
    {
        if ($this->cache->tags($this->cacheTag)->has($steamId)) {
            $this->currentData = $this->cache->tags($this->cacheTag)->get($steamId);
        }

        $inventory = $this->getSteamInventory($steamId, $appId, $contextId);

        if (is_object($inventory)) {
            $minutes = Config::get('braseidon.steaminventory.cache_time');
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
    public function getSteamInventory($steamId, $appId, $contextId)
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

        $data = object_get($this->currentData, 'rgInventory', false);

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

        $data = object_get($this->currentData, 'rgDescriptions', false);
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
            if (object_get($dataItem, 'tradable') !== 1) {
                continue;
            }

            $desc = $this->parseItemDescription($dataItem->descriptions);
            $tags = $this->parseItemTags($dataItem->tags);
            $cat  = array_get($tags, 'Category', '');

            $array = [
                    'appid'           => object_get($dataItem, 'appid'),            // 730
                    'instanceid'      => object_get($dataItem, 'instanceid'),       //
                    'name'            => object_get($dataItem, 'name'),             // P250 | Sand Dune
                    'market_name'     => object_get($dataItem, 'market_name'),      // P250 | Sand Dune (Field-Tested)
                    'classid'         => object_get($dataItem, 'classid'),          // 310777928
                    'icon_url'        => object_get($dataItem, 'icon_url'),
                    'icon_url_large'  => object_get($dataItem, 'icon_url_large'),
                    'weapon'          => array_get($tags, 'Weapon'),               // P250
                    'type'            => array_get($tags, 'Type'),                 // Pistol
                    'exterior'        => array_get($tags, 'Exterior'),             // Field-Tested
                    'stattrack'       => (stripos($cat, 'StatTrak') !== false) ? true : false,
                    'quality'         => array_get($tags, 'Quality'),              // Consumer Grade
                    'collection'      => array_get($tags, 'Collection'),           // The Dust 2 Collection
                    'description'     => $desc,
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
            $parsed[$tag->category_name] = $tag->name;
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
