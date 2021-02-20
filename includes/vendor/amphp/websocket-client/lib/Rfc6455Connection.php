<?php

namespace Amp\Websocket;

use Amp\ByteStream\IteratorStream;
use Amp\Coroutine;
use Amp\Deferred;
use Amp\Emitter;
use Amp\Loop;
use Amp\Promise;
use Amp\Socket\Socket;
use Amp\Success;

final class Rfc6455Connection implements Connection {
    /** @var Options */
    private $options;

    /** @var Socket */
    private $socket;

    /** @var array[] */
    private $headers;

    /** @var \Generator */
    private $parser;

    public $pingCount = 0;
    public $pongCount = 0;

    private $emitBuffer = "";

    /** @var Emitter */
    private $currentMessageEmitter;

    /** @var Message[] */
    private $messages = [];

    /** @var Deferred */
    private $nextMessageDeferred;

    /** @var bool */
    private $serverInitiatedClose = false;

    /** @var bool */
    private $parseError = false;

    /** @var Promise|null */
    private $lastWrite;

    /** @var Promise */
    private $lastEmit;

    /** @var string */
    private $timeoutWatcher;

    // getInfo() properties
    private $connectedAt;
    private $closedAt = 0;
    private $lastReadAt = 0;
    private $lastSentAt = 0;
    private $lastDataReadAt = 0;
    private $lastDataSentAt = 0;
    private $bytesRead = 0;
    private $bytesSent = 0;
    private $framesRead = 0;
    private $framesSent = 0;
    private $messagesRead = 0;
    private $messagesSent = 0;
    private $closeCode;
    private $closeReason;

    /* Frame control bits */
    const FIN = 0b1;
    const RSV_NONE = 0b000;
    const OP_CONT = 0x00;
    const OP_TEXT = 0x01;
    const OP_BIN = 0x02;
    const OP_CLOSE = 0x08;
    const OP_PING = 0x09;
    const OP_PONG = 0x0A;

    const CONTROL = -1;
    const ERROR = -2;

    public function __construct(Socket $socket, array $headers, string $buffer, Options $options) {
        $this->headers = $headers;
        $this->options = $options;

        $this->connectedAt = \time();
        $this->socket = $socket;
        $this->parser = $this->parser();

        if ($buffer !== '') {
            $this->lastReadAt = \time();
            $this->bytesRead += \strlen($buffer);
            $this->framesRead += $this->parser->send($buffer);
        }

        Promise\rethrow(new Coroutine($this->read()));
    }

    /** @inheritdoc */
    public function getHeaders(): array {
        return $this->headers;
    }

    /** @inheritdoc */
    public function getHeader(string $field) {
        return $this->headers[\strtolower($field)][0] ?? null;
    }

    /** @inheritdoc */
    public function getHeaderArray(string $field): array {
        return $this->headers[\strtolower($field)] ?? [];
    }

    /** @inheritdoc */
    public function close(int $code = Code::NORMAL_CLOSE, string $reason = '') {
        // Only proceed if we haven't already begun the close handshake elsewhere
        if ($this->closedAt) {
            return;
        }

        $this->closeCode = $code;
        $this->closeReason = $reason;

        $this->sendCloseFrame($code, $reason);

        $exception = new ClosedException('The connection was closed: ' . $reason, $code, $reason);

        if ($this->currentMessageEmitter) {
            $emitter = $this->currentMessageEmitter;
            $this->currentMessageEmitter = null;
            $emitter->fail($exception);
        }

        if ($this->nextMessageDeferred) {
            $deferred = $this->nextMessageDeferred;
            $this->nextMessageDeferred = null;

            if ($this->serverInitiatedClose || $this->parseError) {
                $deferred->fail($exception);
            } else {
                $deferred->resolve();
            }
        }

        $this->timeoutWatcher = Loop::delay($this->options->getClosePeriod() * 1000, function () {
            $this->unloadServer();
        });

        // Don't unload the client here, it will be unloaded upon timeout
    }

    /** @inheritdoc */
    public function isClosed(): bool {
        return (bool) $this->closedAt;
    }

    private function sendCloseFrame(int $code, string $message): Promise {
        $promise = $this->write(self::OP_CLOSE, \pack('n', $code) . $message);
        $promise->onResolve(function () {
            $this->socket->close();
        });

        $this->closedAt = \time();

        return $promise;
    }

    private function unloadServer() {
        $this->parser = null;
        $this->socket->close();

        if ($this->timeoutWatcher) {
            Loop::cancel($this->timeoutWatcher);
        }

        $exception = new WebSocketException('The connection was closed');

        // fail not yet terminated message streams; they *must not* be failed before client is removed
        if ($this->currentMessageEmitter) {
            $this->currentMessageEmitter->fail($exception);
        }

        if ($this->nextMessageDeferred && ($this->serverInitiatedClose || $this->parseError)) {
            $deferred = $this->nextMessageDeferred;
            $this->nextMessageDeferred = null;
            $deferred->fail(new ClosedException('The connection was closed: ' . $this->closeReason, $this->closeCode, $this->closeReason));
        }
    }

    private function onParsedControlFrame(int $opcode, string $data) {
        if ($this->closedAt) {
            return;
        }

        switch ($opcode) {
            case self::OP_CLOSE:
                if ($this->closedAt) {
                    $this->unloadServer();
                } else {
                    $length = \strlen($data);
                    if ($length === 0) {
                        $this->close();
                        return;
                    } elseif ($length < 2) {
                        $this->close(Code::PROTOCOL_ERROR, 'Close code must be two bytes');
                        return;
                    }

                    $code = \current(\unpack('n', \substr($data, 0, 2)));
                    $reason = \substr($data, 2);

                    $this->serverInitiatedClose = true;

                    // Note: There's a test for 1004, but only 1005 and 1006 must not be used for closing by an endpoint
                    if ($code < 1000 || $code === 1005 || $code === 1006 || $code === 1015) {
                        $this->close(Code::PROTOCOL_ERROR, 'Invalid close code');

                        return;
                    }

                    if ($this->options->isValidateUtf8() && !\preg_match('//u', $reason)) {
                        $this->close(Code::INCONSISTENT_FRAME_DATA_TYPE, 'Close reason must be valid UTF-8');
                    } else {
                        $this->close($code, $reason);
                    }
                }
                break;

            case self::OP_PING:
                $this->write(self::OP_PONG, $data);
                break;

            case self::OP_PONG:
                // We need a min() here, else someone might just send a pong frame with a very high pong count and leave TCP connection in open state...
                $this->pongCount = \min($this->pingCount, $data);
                break;
        }
    }

    private function onParsedData(int $opcode, string $data, bool $terminated) {
        if ($this->closedAt) {
            return;
        }

        $this->lastDataReadAt = \time();

        if (!$this->currentMessageEmitter) {
            if ($opcode === self::OP_CONT) {
                $this->onParsedError(Code::PROTOCOL_ERROR, 'Nothing to continue');

                return;
            }

            $binary = $opcode === self::OP_BIN;

            $this->currentMessageEmitter = new Emitter;

            if ($this->nextMessageDeferred) {
                $deferred = $this->nextMessageDeferred;
                $this->nextMessageDeferred = null;
                $deferred->resolve(new Message(new IteratorStream($this->currentMessageEmitter->iterate()), $binary));
            } else {
                $this->messages[] = new Message(new IteratorStream($this->currentMessageEmitter->iterate()), $binary);
            }
        } elseif ($opcode !== self::OP_CONT) {
            $this->onParsedError(Code::PROTOCOL_ERROR, 'Non-terminated message was not continued');

            return;
        }

        $this->emitBuffer .= $data;

        if ($terminated || \strlen($this->emitBuffer) >= $this->options->getStreamThreshold()) {
            $promise = $this->currentMessageEmitter->emit($this->emitBuffer);
            $this->lastEmit = $this->nextMessageDeferred ? null : $promise;
            $this->emitBuffer = '';
        }

        if ($terminated) {
            $emitter = $this->currentMessageEmitter;
            $this->currentMessageEmitter = null;
            $emitter->complete();

            ++$this->messagesRead;
        }
    }

    private function onParsedError(int $code, string $message) {
        if ($this->closedAt) {
            return;
        }

        $this->parseError = true;
        $this->close($code, $message);
    }

    private function read(): \Generator {
        while (($chunk = yield $this->socket->read()) !== null) {
            $this->lastReadAt = \time();
            $this->bytesRead += \strlen($chunk);
            $this->framesRead += $this->parser->send($chunk);

            if ($this->lastEmit) {
                yield $this->lastEmit;
            }
        }

        if (!$this->closedAt) {
            $this->closedAt = \time();
            $this->closeCode = Code::ABNORMAL_CLOSE;
            $this->closeReason = 'Client closed the underlying TCP connection';
            $this->serverInitiatedClose = true;
        }

        $this->unloadServer();
    }

    private function compile(int $opcode, string $message, bool $fin): string {
        $rsv = 0b000; // @TODO Add filter mechanism (e.g. for gzip encoding)

        $len = \strlen($message);
        $w = \chr(($fin << 7) | ($rsv << 4) | $opcode);

        if ($len > 0xFFFF) {
            $w .= "\xFF" . \pack('J', $len);
        } elseif ($len > 0x7D) {
            $w .= "\xFE" . \pack('n', $len);
        } else {
            $w .= \chr($len | 0x80);
        }

        $mask = \pack('N', \random_int(\PHP_INT_MIN, \PHP_INT_MAX));

        $w .= $mask;
        $w .= $message ^ \str_repeat($mask, ($len + 3) >> 2);

        return $w;
    }

    private function write(int $opcode, string $data, bool $terminated = true): Promise {
        $frame = $this->compile($opcode, $data, $terminated);

        $this->framesSent++;
        $this->bytesSent += \strlen($frame);
        $this->lastSentAt = \time();

        return $this->socket->write($frame);
    }

    /** @inheritdoc */
    public function send(string $data): Promise {
        $this->messagesSent++;

        \assert(\preg_match('//u', $data), 'non-binary data needs to be UTF-8 compatible');

        return $this->lastWrite = new Coroutine($this->doSend(self::OP_TEXT, $data));
    }

    /** @inheritdoc */
    public function sendBinary(string $data): Promise {
        $this->messagesSent++;

        return $this->lastWrite = new Coroutine($this->doSend(self::OP_BIN, $data));
    }

    private function doSend(int $opcode, string $data): \Generator {
        if ($this->lastWrite) {
            yield $this->lastWrite;
        }

        try {
            $bytes = 0;

            if (\strlen($data) > $this->options->getFrameSplitThreshold()) {
                $len = \strlen($data);
                $slices = \ceil($len / $this->options->getFrameSplitThreshold());
                $chunks = \str_split($data, \ceil($len / $slices));
                $final = \array_pop($chunks);
                foreach ($chunks as $chunk) {
                    $bytes += yield $this->write($opcode, $chunk, false);
                    $opcode = self::OP_CONT;
                }
                $bytes += yield $this->write($opcode, $final);
            } else {
                $bytes = yield $this->write($opcode, $data);
            }
        } catch (\Throwable $exception) {
            $this->close();
            throw $exception;
        }

        return $bytes;
    }

    /** @inheritdoc */
    public function receive(): Promise {
        if ($this->nextMessageDeferred) {
            throw new \Error('Await the previous promise returned from receive() before calling receive() again.');
        }

        // There might be messages already buffered and a close frame already received
        if ($this->messages) {
            $message = \reset($this->messages);
            unset($this->messages[\key($this->messages)]);

            return new Success($message);
        }

        if ($this->isClosed()) {
            // User kept in while loop after previous promise already resolved
            if ($this->serverInitiatedClose) {
                throw new ClosedException('The connection was closed: ' . $this->closeReason, $this->closeCode, $this->closeReason);
            }

            // Might happen if close() is called outside the receive coroutine.
            // Succeed with null instead of erroring out just as with a pending receive on close.
            return new Success;
        }

        $this->nextMessageDeferred = new Deferred;

        return $this->nextMessageDeferred->promise();
    }

    /** @inheritdoc */
    public function getInfo(): array {
        return [
            'bytes_read' => $this->bytesRead,
            'bytes_sent' => $this->bytesSent,
            'frames_read' => $this->framesRead,
            'frames_sent' => $this->framesSent,
            'messages_read' => $this->messagesRead,
            'messages_sent' => $this->messagesSent,
            'connected_at' => $this->connectedAt,
            'closed_at' => $this->closedAt,
            'close_code' => $this->closeCode,
            'close_reason' => $this->closeReason,
            'last_read_at' => $this->lastReadAt,
            'last_sent_at' => $this->lastSentAt,
            'last_data_read_at' => $this->lastDataReadAt,
            'last_data_sent_at' => $this->lastDataSentAt,
        ];
    }

    /**
     * A stateful generator WebSocket frame parser.
     *
     * @return \Generator
     */
    private function parser(): \Generator {
        // @TODO add minimum average frame size rate threshold to prevent tiny-frame DoS
        $maxFrameSize = $this->options->getMaximumFrameSize();
        $maxMsgSize = $this->options->getMaximumMessageSize();
        $textOnly = $this->options->isTextOnly();
        $doUtf8Validation = $validateUtf8 = $this->options->isValidateUtf8();

        $dataMsgBytesRecd = 0;
        $savedBuffer = '';

        $buffer = yield;
        $offset = 0;
        $bufferSize = \strlen($buffer);
        $frames = 0;

        while (1) {
            if ($bufferSize < 2) {
                $buffer = \substr($buffer, $offset);
                $offset = 0;
                do {
                    $buffer .= yield $frames;
                    $bufferSize = \strlen($buffer);
                    $frames = 0;
                } while ($bufferSize < 2);
            }

            $firstByte = \ord($buffer[$offset]);
            $secondByte = \ord($buffer[$offset + 1]);

            $offset += 2;
            $bufferSize -= 2;

            $fin = (bool) ($firstByte & 0b10000000);
            $rsv = ($firstByte & 0b01110000) >> 4;
            $opcode = $firstByte & 0b00001111;
            $isMasked = (bool) ($secondByte & 0b10000000);
            $maskingKey = null;
            $frameLength = $secondByte & 0b01111111;

            if ($rsv !== 0) {
                $this->onParsedError(Code::PROTOCOL_ERROR, 'RSV must be 0 if no extensions are negotiated');
            }

            if ($opcode >= 3 && $opcode <= 7) {
                $this->onParsedError(Code::PROTOCOL_ERROR, 'Use of reserved non-control frame opcode');
            }

            if ($opcode >= 11 && $opcode <= 15) {
                $this->onParsedError(Code::PROTOCOL_ERROR, 'Use of reserved control frame opcode');
            }

            $isControlFrame = $opcode >= 0x08;
            if ($validateUtf8 && $opcode !== self::OP_CONT && !$isControlFrame) {
                $doUtf8Validation = $opcode === self::OP_TEXT;
            }

            if ($frameLength === 0x7E) {
                if ($bufferSize < 2) {
                    $buffer = \substr($buffer, $offset);
                    $offset = 0;
                    do {
                        $buffer .= yield $frames;
                        $bufferSize = \strlen($buffer);
                        $frames = 0;
                    } while ($bufferSize < 2);
                }

                $frameLength = \unpack('n', $buffer[$offset] . $buffer[$offset + 1])[1];
                $offset += 2;
                $bufferSize -= 2;
            } elseif ($frameLength === 0x7F) {
                if ($bufferSize < 8) {
                    $buffer = \substr($buffer, $offset);
                    $offset = 0;
                    do {
                        $buffer .= yield $frames;
                        $bufferSize = \strlen($buffer);
                        $frames = 0;
                    } while ($bufferSize < 8);
                }

                $lengthLong32Pair = \unpack('N2', \substr($buffer, $offset, 8));
                $offset += 8;
                $bufferSize -= 8;

                if (PHP_INT_MAX === 0x7fffffff) {
                    if ($lengthLong32Pair[1] !== 0 || $lengthLong32Pair[2] < 0) {
                        $this->onParsedError(
                            Code::MESSAGE_TOO_LARGE,
                            'Received payload exceeds maximum allowable size'
                        );
                        return;
                    }
                    $frameLength = $lengthLong32Pair[2];
                } else {
                    $frameLength = ($lengthLong32Pair[1] << 32) | $lengthLong32Pair[2];
                    if ($frameLength < 0) {
                        $this->onParsedError(
                            Code::PROTOCOL_ERROR,
                            'Most significant bit of 64-bit length field set'
                        );
                        return;
                    }
                }
            }

            if ($isMasked) {
                $this->onParsedError(
                    Code::PROTOCOL_ERROR,
                    'Payload must not be masked to client'
                );
                return;
            }

            if ($isControlFrame) {
                if (!$fin) {
                    $this->onParsedError(
                        Code::PROTOCOL_ERROR,
                        'Illegal control frame fragmentation'
                    );
                    return;
                }

                if ($frameLength > 125) {
                    $this->onParsedError(
                        Code::PROTOCOL_ERROR,
                        'Control frame payload must be of maximum 125 bytes or less'
                    );
                    return;
                }
            }

            if ($maxFrameSize && $frameLength > $maxFrameSize) {
                $this->onParsedError(
                    Code::MESSAGE_TOO_LARGE,
                    'Received payload exceeds maximum allowable size'
                );
                return;
            }

            if ($maxMsgSize && ($frameLength + $dataMsgBytesRecd) > $maxMsgSize) {
                $this->onParsedError(
                    Code::MESSAGE_TOO_LARGE,
                    'Received payload exceeds maximum allowable size'
                );
                return;
            }

            if ($textOnly && $opcode === 0x02) {
                $this->onParsedError(
                    Code::UNACCEPTABLE_TYPE,
                    'BINARY opcodes (0x02) not accepted'
                );
                return;
            }

            while ($bufferSize < $frameLength) {
                $chunk = yield $frames;
                $buffer .= $chunk;
                $bufferSize += \strlen($chunk);
                $frames = 0;
            }

            if (!$isControlFrame) {
                $dataMsgBytesRecd += $frameLength;
            }

            $payload = \substr($buffer, $offset, $frameLength);
            $offset += $frameLength;
            $bufferSize -= $frameLength;

            if ($isControlFrame) {
                $this->onParsedControlFrame($opcode, $payload);
            } else {
                if ($savedBuffer !== '') {
                    $payload = $savedBuffer . $payload;
                    $savedBuffer = '';
                }

                if ($doUtf8Validation) {
                    if ($fin) {
                        $i = \preg_match('//u', $payload) ? 0 : 8;
                    } else {
                        $string = $payload;
                        for ($i = 0; !\preg_match('//u', $payload) && $i < 8; $i++) {
                            $payload = \substr($payload, 0, -1);
                        }
                        if ($i > 0) {
                            $savedBuffer = \substr($string, -$i);
                        }
                    }
                    if ($i === 8) {
                        $this->onParsedError(
                            Code::INCONSISTENT_FRAME_DATA_TYPE,
                            'Invalid TEXT data; UTF-8 required'
                        );
                        return;
                    }
                }

                if ($fin) {
                    $dataMsgBytesRecd = 0;
                }

                $this->onParsedData($opcode, $payload, $fin);

                if ($this->parseError) {
                    return;
                }
            }

            $frames++;
        }
    }
}
