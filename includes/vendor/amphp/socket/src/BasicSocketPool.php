<?php

namespace Amp\Socket;

use Amp\CancellationToken;
use Amp\CancelledException;
use Amp\Failure;
use Amp\Loop;
use Amp\Promise;
use Amp\Struct;
use Amp\Success;
use Amp\Uri\Uri;
use function Amp\call;

final class BasicSocketPool implements SocketPool {
    private $sockets = [];
    private $socketIdUriMap = [];
    private $pendingCount = [];

    private $idleTimeout;
    private $socketContext;

    public function __construct(int $idleTimeout = 10000, ClientConnectContext $socketContext = null) {
        $this->idleTimeout = $idleTimeout;
        $this->socketContext = $socketContext ?? new ClientConnectContext;
    }

    private function normalizeUri(string $uri): string {
        if (stripos($uri, 'unix://') === 0) {
            return $uri;
        }

        return (new Uri($uri))->normalize();
    }

    /** @inheritdoc */
    public function checkout(string $uri, CancellationToken $token = null): Promise {
        // A request might already be cancelled before we reach the checkout, so do not even attempt to checkout in that
        // case. The weird logic is required to throw the token's exception instead of creating a new one.
        if ($token && $token->isRequested()) {
            try {
                $token->throwIfRequested();
            } catch (CancelledException $e) {
                return new Failure($e);
            }
        }

        $uri = $this->normalizeUri($uri);

        if (empty($this->sockets[$uri])) {
            return $this->checkoutNewSocket($uri, $token);
        }

        foreach ($this->sockets[$uri] as $socketId => $struct) {
            if (!$struct->isAvailable) {
                continue;
            } elseif (!\is_resource($struct->resource) || \feof($struct->resource)) {
                $this->clear($struct->socket);
                continue;
            }

            $struct->isAvailable = false;

            if ($struct->idleWatcher !== null) {
                Loop::disable($struct->idleWatcher);
            }

            return new Success($struct->socket);
        }

        return $this->checkoutNewSocket($uri, $token);
    }

    private function checkoutNewSocket(string $uri, CancellationToken $token = null): Promise {
        return call(function () use ($uri, $token) {
            $this->pendingCount[$uri] = ($this->pendingCount[$uri] ?? 0) + 1;

            try {
                /** @var ClientSocket $socket */
                $socket = yield connect($uri, $this->socketContext, $token);
            } finally {
                if (--$this->pendingCount[$uri] === 0) {
                    unset($this->pendingCount[$uri]);
                }
            }

            $socketId = (int) $socket->getResource();

            $struct = new class {
                use Struct;

                public $id;
                public $uri;
                public $resource;
                public $isAvailable;
                public $socket;
                public $idleWatcher;
            };

            $struct->id = $socketId;
            $struct->uri = $uri;
            $struct->resource = $socket->getResource();
            $struct->isAvailable = false;
            $struct->socket = $socket;

            $hash = \spl_object_hash($socket);
            $this->sockets[$uri][$hash] = $struct;
            $this->socketIdUriMap[$hash] = $uri;

            return $socket;
        });
    }

    /** @inheritdoc */
    public function clear(ClientSocket $socket) {
        $hash = \spl_object_hash($socket);

        if (!isset($this->socketIdUriMap[$hash])) {
            throw new \Error(
                sprintf('Unknown socket: %d', $hash)
            );
        }

        $uri = $this->socketIdUriMap[$hash];
        $struct = $this->sockets[$uri][$hash];

        if ($struct->idleWatcher) {
            Loop::cancel($struct->idleWatcher);
        }

        unset(
            $this->sockets[$uri][$hash],
            $this->socketIdUriMap[$hash]
        );

        if (empty($this->sockets[$uri])) {
            unset($this->sockets[$uri]);
        }
    }

    /** @inheritdoc */
    public function checkin(ClientSocket $socket) {
        $hash = \spl_object_hash($socket);

        if (!isset($this->socketIdUriMap[$hash])) {
            throw new \Error(
                \sprintf('Unknown socket: %d', $hash)
            );
        }

        $uri = $this->socketIdUriMap[$hash];

        if (!\is_resource($socket->getResource()) || \feof($socket->getResource())) {
            $this->clear($socket);
            return;
        }

        $struct = $this->sockets[$uri][$hash];
        $struct->isAvailable = true;

        if (isset($struct->idleWatcher)) {
            Loop::enable($struct->idleWatcher);
        } else {
            $struct->idleWatcher = Loop::delay($this->idleTimeout, function () use ($struct) {
                $this->clear($struct->socket);
            });

            Loop::unreference($struct->idleWatcher);
        }
    }
}
