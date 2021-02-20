<?php

namespace Amp\Http;

use PHPUnit\Framework\TestCase;

class ParseFieldValueComponentsTest extends TestCase
{
    public function test()
    {
        self::assertSame([
            ['no-cache', ''],
            ['no-store', ''],
            ['must-revalidate', ''],
        ], $this->parse('no-cache, no-store, must-revalidate'));

        self::assertSame([
            ['public', ''],
            ['max-age', '31536000'],
        ], $this->parse('public, max-age=31536000'));

        self::assertSame([
            ['private', 'foo, bar'],
            ['max-age', '31536000'],
        ], $this->parse('private="foo, bar", max-age=31536000'));

        self::assertNull($this->parse('private="foo, bar, max-age=31536000'));

        self::assertSame([
            ['private', 'foo"bar'],
            ['max-age', '31536000'],
        ], $this->parse('private="foo\"bar", max-age=31536000'));

        self::assertSame([
            ['private', 'foo""bar'],
            ['max-age', '31536000'],
        ], $this->parse('private="foo\"\"bar", max-age=31536000'));

        self::assertSame([
            ['private', 'foo\\'],
            ['bar', ''],
        ], $this->parse('private="foo\\\\", bar'));

        self::assertSame([
            ['private', 'foo'],
            ['private', 'bar'],
        ], $this->parse('private="foo", private=bar'));
    }

    private function parse(string $headerValue)
    {
        return parseFieldValueComponents($this->createMessage(['cache-control' => $headerValue]), 'cache-control');
    }

    private function createMessage(array $headers): Message
    {
        return new class($headers) extends Message {
            public function __construct(array $headers)
            {
                $this->setHeaders($headers);
            }
        };
    }
}
