# websocket

[![Build Status](https://img.shields.io/travis/amphp/websocket/master.svg?style=flat-square)](https://travis-ci.org/amphp/websocket)
[![CoverageStatus](https://img.shields.io/coveralls/amphp/websocket/master.svg?style=flat-square)](https://coveralls.io/github/amphp/websocket?branch=master)
![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)

`amphp/websocket` is an async WebSocket client for PHP based on Amp.

## Installation

This package can be installed as a [Composer](https://getcomposer.org/) dependency.

```
composer require amphp/websocket
```

## Requirements

* PHP 7.0+

## Documentation & Examples

More extensive code examples reside in the [`examples`](examples) directory.

```php
use Amp\Delayed;
use Amp\Websocket;

// Connects to the websocket endpoint in demo.php provided with Aerys (https://github.com/amphp/aerys).
Amp\Loop::run(function () {
    /** @var \Amp\Websocket\Connection $connection */
    $connection = yield Websocket\connect("ws://localhost:1337/ws");
    yield $connection->send("Hello!");

    $i = 0;

    while ($message = yield $connection->receive()) {
        $payload = yield $message->buffer();
        printf("Received: %s\n", $payload);

        if ($payload === "Goodbye!") {
            $connection->close();
            break;
        }

        yield new Delayed(1000);

        if ($i < 3) {
            yield $connection->send("Ping: " . ++$i);
        } else {
            yield $connection->send("Goodbye!");
        }
    }
});
```

## Versioning

`amphp/websocket` follows the [semver](http://semver.org/) semantic versioning specification like all other `amphp` packages.

## Security

If you discover any security related issues, please email [`contact@amphp.org`](mailto:contact@amphp.org) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information.
