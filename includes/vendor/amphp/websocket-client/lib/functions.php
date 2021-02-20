<?php

namespace Amp\Websocket;

use Amp\Promise;
use Amp\Socket;
use Amp\Socket\ClientConnectContext;
use Amp\Socket\ClientSocket;
use Amp\Socket\ClientTlsContext;
use function Amp\call;

/**
 * @param string|\Amp\Websocket\Handshake       $handshake
 * @param \Amp\Socket\ClientConnectContext|null $connectContext
 * @param \Amp\Socket\ClientTlsContext|null     $tlsContext
 * @param Options                               $options
 *
 * @return \Amp\Promise<\Amp\WebSocket\Connection>
 *
 * @throws \TypeError If $handshake is not a string or instance of \Amp\WebSocket\Handshake.
 */
function connect($handshake, ClientConnectContext $connectContext = null, ClientTlsContext $tlsContext = null, Options $options = null): Promise {
    if (\is_string($handshake)) {
        $handshake = new Handshake($handshake);
    } elseif (!$handshake instanceof Handshake) {
        throw new \TypeError(\sprintf('Must provide an instance of %s or a URL as a string', Handshake::class));
    }

    $options = $options ?? new Options;

    return call(function () use ($handshake, $connectContext, $tlsContext, $options) {
        if ($handshake->isEncrypted()) {
            /** @var ClientSocket $socket */
            $socket = yield Socket\cryptoConnect($handshake->getRemoteAddress(), $connectContext, $tlsContext);
        } else {
            /** @var ClientSocket $socket */
            $socket = yield Socket\connect($handshake->getRemoteAddress(), $connectContext);
        }

        yield $socket->write($handshake->generateRequest());

        $buffer = '';

        while (($chunk = yield $socket->read()) !== null) {
            $buffer .= $chunk;

            if ($position = \strpos($buffer, "\r\n\r\n")) {
                $headerBuffer = \substr($buffer, 0, $position + 4);
                $buffer = \substr($buffer, $position + 4);

                $headers = $handshake->decodeResponse($headerBuffer);

                return new Rfc6455Connection($socket, $headers, $buffer, $options);
            }
        }

        throw new WebSocketException('Failed to read response from server');
    });
}
