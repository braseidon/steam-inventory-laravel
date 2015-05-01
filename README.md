# Braseidon\SteamInventory (For Laravel 5)

[![Author](http://img.shields.io/badge/author-@BraSeidon-blue.svg?style=flat-square)](https://twitter.com/BraSeidon)
[![Latest Version](https://img.shields.io/github/release/braseidon/steam-inventory-laravel.svg?style=flat-square)](https://github.com/braseidon/steam-inventory-laravel/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/braseidon/steam-inventory-laravel/blob/master/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/braseidon/steam-inventory-laravel.svg?style=flat-square)](https://packagist.org/packages/braseidon/steam-inventory-laravel)

SteamInventory is a wrapper around the JSON Steam API that grabs a user's items - all packaged up for Laravel.

## Highlights

- Uses the fast & free JSON Steam API to fetch data
- Automagically converts 32 bit and 64 bit Steam ID's to the appropriate type
- Utilizes Laravel's Caching system

# Installation

Braseidon\SteamInventory is available via Composer:

```bash
$ composer require braseidon/steam-inventory-laravel
```

After pulling in the package via Composer, you need to include the Service Provider in your app's <code>config/app.php</code>.

```php
'providers' => [
    // ...
    'Braseidon\SteamInventory\Support\SteamInventoryServiceProvider',
];
```

## Documentation

<strong>Documentation will be finished when v1.0.0 is up.</strong>

## Contributing

Contributions are more than welcome and will be fully credited.

## Security

If you discover any security related issues, please email brandon@poseidonwebstudios.com instead of using the issue tracker.

## Credits

- [Brandon Johnson](https://github.com/braseidon)
- [All Contributors](https://github.com/braseidon/steam-inventory-laravel/graphs/contributors)


## License

The MIT License (MIT). Please see [LICENSE](https://github.com/braseidon/steam-inventory-laravel/blob/master/LICENSE) for more information.