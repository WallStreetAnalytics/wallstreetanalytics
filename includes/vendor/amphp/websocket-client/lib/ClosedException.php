<?php

namespace Amp\Websocket;

final class ClosedException extends WebSocketException {
    /** @var string */
    private $reason;

    public function __construct(string $message, int $code, string $reason) {
        parent::__construct($message, $code);

        $this->reason = $reason;
    }

    public function getReason(): string {
        return $this->reason;
    }
}
