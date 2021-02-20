<?php

namespace Amp\Websocket;

use Amp\Promise;

interface Connection {
    /**
     * Exposes all headers of the handshake response.
     *
     * **Format**
     *
     * ```
     * [
     *     'header-name' => [
     *         'value1',
     *         'value2',
     *     ],
     *     'other-header' => [
     *         'value-1'
     *     ],
     * ]
     * ```
     *
     * All header name keys must be lowercase.
     *
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Provides access to a specific header.
     *
     * @param string $field Case-insensitive header name.
     *
     * @return string|null First header value or null if header was not present.
     */
    public function getHeader(string $field);

    /**
     * Provides access to a specific header.
     *
     * @param string $field Case-insensitive header name.
     *
     * @return array All header values, might be an empty array if header was not present.
     */
    public function getHeaderArray(string $field): array;

    /**
     * Sends text data to the remote.
     *
     * All data sent with this method must be valid UTF-8. Use `sendBinary()` if you want to send binary data.
     *
     * @param string $data Payload to send.
     *
     * @return Promise
     *
     * @see Connection::sendBinary()
     */
    public function send(string $data): Promise;

    /**
     * Sends binary data to the remote.
     *
     * @param string $data Payload to send.
     *
     * @return Promise
     *
     * @see Connection::send()
     */
    public function sendBinary(string $data): Promise;

    /**
     * Receive a message from the remote.
     *
     * @return Promise<array<Message>|null> Resolves to message sent by the remote or `null` if the connection was
     *     closed locally.
     */
    public function receive(): Promise;

    /**
     * Check whether the connection has been closed.
     *
     * @return bool Returns `true` as soon as the closing handshake has started by one side (either client or server).
     */
    public function isClosed(): bool;

    /**
     * Closes the connection.
     *
     * @param int    $code Close code.
     * @param string $reason Close reason.
     *
     * @return void
     */
    public function close(int $code = Code::NORMAL_CLOSE, string $reason = '');

    /**
     * Returns connection metadata.
     *
     * ```
     * [
     *     'bytes_read' => int,
     *     'bytes_sent' => int,
     *     'frames_read' => int,
     *     'frames_sent' => int,
     *     'messages_read' => int,
     *     'messages_sent' => int,
     *     'connected_at' => int,
     *     'closed_at' => int,
     *     'last_read_at' => int,
     *     'last_sent_at' => int,
     *     'last_data_read_at' => int,
     *     'last_data_sent_at' => int,
     * ]
     * ```
     *
     * @return array Array in the format described above.
     */
    public function getInfo(): array;
}
