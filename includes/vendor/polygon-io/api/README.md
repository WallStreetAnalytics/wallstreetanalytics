# Polygon.io php api client

[![CircleCI](https://circleci.com/gh/bassochette/polygon.io-php.svg?style=svg)](https://circleci.com/gh/bassochette/polygon.io-php)

## Installation guide

### prerequisite

- [composer](https://getcomposer.org/)
- php > 7.2

### install

``` 
composer require polygonio/api
```

## [Rest API](https://polygon.io/docs/#getting-started)

The `\PolygonIO\rest\Rest` class export 4 modules:

- reference
- stocks
- forex
- crypto

```
<?php
require __DIR__ . '/vendor/autload.php';
use PolygonIO\rest\Rest;

$rest = new Rest('your api key')

print_r($rest->forex->realtimeCurrencyConverion->get('USD', 'EUR', 10));

```

## Websockets

The websocket clients use the Amp event loop. 
You can only use one websocket client by php thread since the event loop is in a blocking while loop.

```
<?php
require __DIR__ . '/vendor/autload.php';
use PolygonIO;

$client = new PolygonIO('your apiKey');

$client->websockets->forex(
    'C.USD',
    function($data) {
        // your handler function
    }
)
```

## Developement

### prerequisite

- [composer](https://getcomposer.org/)
- php > 7.2

### use the tooling

Install dependencies
```
composer require
```

Run the linter
```bash
./vendor/bin/phplint .
```

Run the tests
```
./vendor/bin/phpunit
```