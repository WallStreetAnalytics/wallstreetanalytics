<?php

namespace Amp\Socket;

use Amp\CancellationToken;
use Amp\Deferred;
use Amp\Dns;
use Amp\Loop;
use Amp\NullCancellationToken;
use Amp\Promise;
use Amp\TimeoutException;
use function Amp\call;

/**
 * Listen for client connections on the specified server address.
 *
 * If you want to accept TLS connections, you have to use `yield $socket->enableCrypto()` after accepting new clients.
 *
 * @param string $uri URI in scheme://host:port format. TCP is assumed if no scheme is present.
 * @param ServerListenContext $socketContext Context options for listening.
 * @param ServerTlsContext $tlsContext Context options for TLS connections.
 *
 * @return Server
 *
 * @throws SocketException If binding to the specified URI failed.
 * @throws \Error If an invalid scheme is given.
 */
function listen(string $uri, ServerListenContext $socketContext = null, ServerTlsContext $tlsContext = null): Server {
    $socketContext = $socketContext ?? new ServerListenContext;

    $scheme = \strstr($uri, "://", true);

    if ($scheme === false) {
        $scheme = "tcp";
    }

    if (!\in_array($scheme, ["tcp", "udp", "unix", "udg"])) {
        throw new \Error("Only tcp, udp, unix and udg schemes allowed for server creation");
    }

    if ($tlsContext) {
        $context = \array_merge(
            $socketContext->toStreamContextArray(),
            $tlsContext->toStreamContextArray()
        );
    } else {
        $context = $socketContext->toStreamContextArray();
    }

    $context = \stream_context_create($context);

    // Error reporting suppressed since stream_socket_server() emits an E_WARNING on failure (checked below).
    $server = @\stream_socket_server($uri, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

    if (!$server || $errno) {
        throw new SocketException(\sprintf("Could not create server %s: [Error: #%d] %s", $uri, $errno, $errstr), $errno);
    }

    return new Server($server, Socket::DEFAULT_CHUNK_SIZE);
}

/**
 * Asynchronously establish a socket connection to the specified URI.
 *
 * @param string                 $uri URI in scheme://host:port format. TCP is assumed if no scheme is present.
 * @param ClientConnectContext   $socketContext Socket connect context to use when connecting.
 * @param CancellationToken|null $token
 *
 * @return Promise<\Amp\Socket\ClientSocket>
 */
function connect(string $uri, ClientConnectContext $socketContext = null, CancellationToken $token = null): Promise {
    return call(function () use ($uri, $socketContext, $token) {
        $socketContext = $socketContext ?? new ClientConnectContext;
        $token = $token ?? new NullCancellationToken;
        $attempt = 0;
        $uris = [];
        $failures = [];

        list($scheme, $host, $port) = Internal\parseUri($uri);

        if ($host[0] === '[') {
            $host = substr($host, 1, -1);
        }

        if ($port === 0 || @\inet_pton($host)) {
            // Host is already an IP address or file path.
            $uris = [$uri];
        } else {
            // Host is not an IP address, so resolve the domain name.
            $records = yield Dns\resolve($host, $socketContext->getDnsTypeRestriction());

            // Usually the faster response should be preferred, but we don't have a reliable way of determining IPv6
            // support, so we always prefer IPv4 here.
            \usort($records, function (Dns\Record $a, Dns\Record $b) {
                return $a->getType() - $b->getType();
            });

            foreach ($records as $record) {
                /** @var Dns\Record $record */
                if ($record->getType() === Dns\Record::AAAA) {
                    $uris[] = \sprintf("%s://[%s]:%d", $scheme, $record->getValue(), $port);
                } else {
                    $uris[] = \sprintf("%s://%s:%d", $scheme, $record->getValue(), $port);
                }
            }
        }

        $flags = \STREAM_CLIENT_CONNECT | \STREAM_CLIENT_ASYNC_CONNECT;
        $timeout = $socketContext->getConnectTimeout();

        foreach ($uris as $builtUri) {
            try {
                $context = \stream_context_create($socketContext->toStreamContextArray());

                if (!$socket = @\stream_socket_client($builtUri, $errno, $errstr, null, $flags, $context)) {
                    throw new ConnectException(\sprintf(
                        "Connection to %s failed: [Error #%d] %s%s",
                        $uri,
                        $errno,
                        $errstr,
                        $failures ? "; previous attempts: " . \implode($failures) : ""
                    ), $errno);
                }

                \stream_set_blocking($socket, false);

                $deferred = new Deferred;
                $watcher = Loop::onWritable($socket, [$deferred, 'resolve']);
                $id = $token->subscribe([$deferred, 'fail']);

                try {
                    yield Promise\timeout($deferred->promise(), $timeout);
                } catch (TimeoutException $e) {
                    throw new ConnectException(\sprintf(
                        "Connecting to %s failed: timeout exceeded (%d ms)%s",
                        $uri,
                        $timeout,
                        $failures ? "; previous attempts: " . \implode($failures) : ""
                    ), 110); // See ETIMEDOUT in http://www.virtsync.com/c-error-codes-include-errno
                } finally {
                    Loop::cancel($watcher);
                    $token->unsubscribe($id);
                }

                // The following hack looks like the only way to detect connection refused errors with PHP's stream sockets.
                if (\stream_socket_get_name($socket, true) === false) {
                    \fclose($socket);
                    throw new ConnectException(\sprintf(
                        "Connection to %s refused%s",
                        $uri,
                        $failures ? "; previous attempts: " . \implode($failures) : ""
                    ), 111); // See ECONNREFUSED in http://www.virtsync.com/c-error-codes-include-errno
                }
            } catch (ConnectException $e) {
                // Includes only error codes used in this file, as error codes on other OS families might be different.
                // In fact, this might show a confusing error message on OS families that return 110 or 111 by itself.
                $knownReasons = [
                    110 => "connection timeout",
                    111 => "connection refused",
                ];

                $code = $e->getCode();
                $reason = $knownReasons[$code] ?? ("Error #" . $code);

                if (++$attempt === $socketContext->getMaxAttempts()) {
                    break;
                }

                $failures[] = "{$uri} ({$reason})";

                continue; // Could not connect to host, try next host in the list.
            }

            return new ClientSocket($socket);
        }

        // This is reached if either all URIs failed or the maximum number of attempts is reached.
        throw $e;
    });
}

/**
 * Asynchronously establish an encrypted TCP connection (non-blocking).
 *
 * Note: Once resolved the socket stream will already be set to non-blocking mode.
 *
 * @param string               $uri
 * @param ClientConnectContext $socketContext
 * @param ClientTlsContext     $tlsContext
 * @param CancellationToken    $token
 *
 * @return Promise<ClientSocket>
 */
function cryptoConnect(
    string $uri,
    ClientConnectContext $socketContext = null,
    ClientTlsContext $tlsContext = null,
    CancellationToken $token = null
): Promise {
    return call(function () use ($uri, $socketContext, $tlsContext, $token) {
        $tlsContext = $tlsContext ?? new ClientTlsContext;

        if ($tlsContext->getPeerName() === null) {
            $tlsContext = $tlsContext->withPeerName(\parse_url($uri, PHP_URL_HOST));
        }

        /** @var ClientSocket $socket */
        $socket = yield connect($uri, $socketContext, $token);

        $promise = $socket->enableCrypto($tlsContext);

        if ($token) {
            $deferred = new Deferred;
            $id = $token->subscribe([$deferred, "fail"]);

            $promise->onResolve(function ($exception) use ($id, $token, $deferred) {
                if ($token->isRequested()) {
                    return;
                }

                $token->unsubscribe($id);

                if ($exception) {
                    $deferred->fail($exception);
                    return;
                }

                $deferred->resolve();
            });

            $promise = $deferred->promise();
        }

        try {
            yield $promise;
        } catch (\Throwable $exception) {
            $socket->close();
            throw $exception;
        }

        return $socket;
    });
}

/**
 * Returns a pair of connected stream socket resources.
 *
 * @return resource[] Pair of socket resources.
 *
 * @throws \Amp\Socket\SocketException If creating the sockets fails.
 */
function pair(): array {
    if (($sockets = @\stream_socket_pair(\stripos(PHP_OS, "win") === 0 ? STREAM_PF_INET : STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP)) === false) {
        $message = "Failed to create socket pair.";
        if ($error = \error_get_last()) {
            $message .= \sprintf(" Errno: %d; %s", $error["type"], $error["message"]);
        }
        throw new SocketException($message);
    }

    return $sockets;
}
