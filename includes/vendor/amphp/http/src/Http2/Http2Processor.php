<?php

namespace Amp\Http\Http2;

interface Http2Processor
{
    public function handlePong(string $data): void;

    public function handlePing(string $data): void;

    public function handleShutdown(int $lastId, int $error): void;

    public function handleStreamWindowIncrement(int $streamId, int $windowSize): void;

    public function handleConnectionWindowIncrement(int $windowSize): void;

    public function handleHeaders(int $streamId, array $pseudo, array $headers, bool $streamEnded): void;

    public function handlePushPromise(int $streamId, int $pushId, array $pseudo, array $headers): void;

    public function handlePriority(int $streamId, int $parentId, int $weight): void;

    public function handleStreamReset(int $streamId, int $errorCode): void;

    public function handleStreamException(Http2StreamException $exception): void;

    public function handleConnectionException(Http2ConnectionException $exception): void;

    public function handleData(int $streamId, string $data): void;

    public function handleSettings(array $settings): void;

    public function handleStreamEnd(int $streamId): void;
}
