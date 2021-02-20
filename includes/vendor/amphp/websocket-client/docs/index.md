---
title: Introduction
permalink: /
---
`amphp/websocket` provides an asynchronous WebSocket client for PHP based on Amp.
WebSockets are full-duplex communication channels, which are mostly used for realtime communication where the HTTP request / response cycle has too much overhead.
They're also used if the server should be able to push data to the client without an explicit request.

There are various use cases for a WebSocket client in PHP, such as consuming realtime APIs, writing tests for a WebSocket server, or controlling web browsers via their remote debugging APIs, which are based on WebSockets.

## Connecting

A new WebSocket connection is created using `Amp\Websocket\connect()`.
It accepts a string as first argument, which must use the `ws` or `wss` (WebSocket over TLS) scheme.
Further optional arguments are `ClientConnectContext`, `ClientTlsContext` and `Options`, which usually don't need to be customized.

If you need to send additional headers with the initial handshake, you can pass a `Handshake` object as first argument instead of a string with the URL.

```php
<?php

require 'vendor/autoload.php';

use Amp\Websocket;

Amp\Loop::run(function () {
    /** @var Websocket\Connection $connection */
    $connection = yield Websocket\connect('ws://localhost:1337/ws');

    // do something
});
```

## Sending Data

WebSocket messages can be sent using the `send()` and `sendBinary()` methods.
Text messages sent with `send()` must be valid UTF-8.
Binary messages send with `sendBinary()` can be arbitrary data.

Both methods return a `Promise` that is resolved as soon as the message is fully written to the send buffer. This doesn't neither mean that the message has been received by the other party nor that the message even left the local system's send buffer, yet.

## Receiving Data

WebSocket messages can be received using the `receive()` method. The `Promise` returned from `receive()` resolves once the client has started to receive a message. This allows streaming WebSocket messages, which might be pretty large. In practice, most messages are rather small, and it's fine buffering them completely. The `Promise` returned from `receive()` resolves to a `Message`, which allows easy buffered and streamed consumption.

{:.note}
> `Amp\Websocket\Message` differs from `Amp\ByteStream\Message`.
> While `Amp\ByteStream\Message` directly implements `Promise`, this is not possible for promise resolution values.
> Instead a consumer has to call `Amp\Websocket\Message::buffer()` which returns a `Promise` resolving to the entire message contents.
>
> A future version of `amphp/byte-stream` will change `Amp\ByteStream\Message` in a similar way or add a replacement.

## Demo

The following example connects to a WebSocket demo server that just echos all messages it receives.

```php
<?php

require 'vendor/autoload.php';

use Amp\Delayed;
use Amp\Websocket;

Amp\Loop::run(function () {
    /** @var Websocket\Connection $connection */
    $connection = yield Websocket\connect('ws://demos.kaazing.com/echo');
    yield $connection->send('Hello!');

    $i = 0;

    /** @var Websocket\Message $message */
    while ($message = yield $connection->receive()) {
        $payload = yield $message->buffer();

        printf("Received: %s\n", $payload);

        if ($payload === 'Goodbye!') {
            $connection->close();
            break;
        }

        yield new Delayed(1000);

        if ($i < 3) {
            yield $connection->send('Ping: ' . ++$i);
        } else {
            yield $connection->send('Goodbye!');
        }
    }
});
```
