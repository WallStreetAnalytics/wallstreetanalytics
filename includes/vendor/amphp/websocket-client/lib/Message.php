<?php

namespace Amp\Websocket;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\Payload;

/**
 * This class allows streamed and buffered access to an `InputStream` similar to `Amp\ByteStream\Message`.
 *
 * `Amp\ByteStream\Message` is not extended due to it implementing `Amp\Promise`, which makes resolving promises with it
 * impossible. `Amp\ByteStream\Message` will probably be adjusted to follow this implementation in the future.
 */
final class Message extends Payload {
    /** @var bool */
    private $binary;

    public function __construct(InputStream $stream, bool $binary) {
        parent::__construct($stream);
        $this->binary = $binary;
    }

    /**
     * @return bool True if the message is UTF-8 text, false if it is binary.
     *
     * @see isBinary
     */
    public function isText(): bool {
        return !$this->binary;
    }

    /**
     * @return bool True if the message is binary, false if it is UTF-8 text.
     *
     * @see isText
     */
    public function isBinary(): bool {
        return $this->binary;
    }
}
