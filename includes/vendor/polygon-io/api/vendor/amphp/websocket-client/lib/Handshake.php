<?php

namespace Amp\Websocket;

use Amp\Http\Rfc7230;
use League\Uri\UriInterface as Uri;
use League\Uri\Ws;

final class Handshake {
    // defined in https://tools.ietf.org/html/rfc6455#section-4
    const ACCEPT_CONCAT = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    const ACCEPT_NONCE_LENGTH = 16;

    /** @var \League\Uri\AbstractUri|Ws */
    private $uri;

    /** @var array */
    private $headers = [];

    /** @var string|null */
    private $accept;

    /**
     * @param string $uri target address of websocket (e.g. ws://foo.bar/baz or wss://crypto.example/?secureConnection)
     */
    public function __construct(string $uri) {
        $this->uri = Ws::createFromString($uri);
    }

    public function addHeader(string $field, string $value): self {
        $this->headers[$field][] = $value;

        return $this;
    }

    public function getUri(): Uri {
        return $this->uri;
    }

    public function getRemoteAddress(): string {
        $defaultPort = $this->isEncrypted() ? 443 : 80;
        return $this->uri->getHost() . ':' . ($this->uri->getPort() ?? $defaultPort);
    }

    public function isEncrypted(): bool {
        return $this->uri->getScheme() === 'wss';
    }

    public function getHeaders(): array {
        return $this->headers;
    }

    public function generateRequest(): string {
        // This has to be generated for each connect attempt, once a new request has been generated,
        // we use the new key for validation.
        $this->accept = \base64_encode(\random_bytes(self::ACCEPT_NONCE_LENGTH));

        $host = $this->uri->getHost();
        $defaultPort = $this->isEncrypted() ? 443 : 80;
        $port = $this->uri->getPort();
        if ($port !== null && $port !== $defaultPort) {
            $host .= ':' . $port;
        }

        $headers = [];

        $headers['connection'][] = 'Upgrade';
        $headers['upgrade'][] = 'websocket';
        $headers['sec-websocket-version'][] = '13';
        $headers['sec-websocket-key'][] = $this->accept;
        $headers['host'][] = $host;

        $headers = \array_merge($headers, $this->headers);

        $headers = Rfc7230::formatHeaders($headers);

        $path = $this->uri->getPath() ?? '/';

        if ($query = $this->uri->getQuery()) {
            $path .= '?' . $query;
        }

        return \sprintf("GET %s HTTP/1.1\r\n%s\r\n", $path, $headers);
    }

    public function decodeResponse(string $headerBuffer): array {
        if (\substr($headerBuffer, -4) !== "\r\n\r\n") {
            throw new WebSocketException('Invalid header provided');
        }

        $position = \strpos($headerBuffer, "\r\n");
        $startLine = \substr($headerBuffer, 0, $position);

        if (!\preg_match("(^HTTP/1.1[\x20\x09]101[\x20\x09]*[^\x01-\x08\x10-\x19]*$)", $startLine)) {
            throw new WebSocketException('Did not receive switching protocols response: ' . $startLine);
        }

        $headerBuffer = \substr($headerBuffer, $position + 2, -2);

        $headers = Rfc7230::parseHeaders($headerBuffer);

        $upgrade = $headers['upgrade'][0] ?? '';
        if (\strtolower($upgrade) !== 'websocket') {
            throw new WebSocketException('Missing "Upgrade: websocket" header.');
        }

        $connection = $headers['connection'][0] ?? '';
        if (!\in_array('upgrade', \array_map('trim', \array_map('strtolower', \explode(',', $connection))), true)) {
            throw new WebSocketException('Missing "Connection: upgrade" header.');
        }

        $secWebsocketAccept = $headers['sec-websocket-accept'][0] ?? '';
        if ($secWebsocketAccept !== \base64_encode(\sha1($this->accept . self::ACCEPT_CONCAT, true))) {
            throw new WebSocketException('Invalid "Sec-WebSocket-Accept" header.');
        }

        // TODO: Check extension header and fail handshake if extensions are not supported

        return $headers;
    }
}
