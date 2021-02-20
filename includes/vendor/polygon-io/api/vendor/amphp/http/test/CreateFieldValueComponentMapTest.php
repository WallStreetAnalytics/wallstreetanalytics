<?php

namespace Amp\Http;

use PHPUnit\Framework\TestCase;

class CreateFieldValueComponentMapTest extends TestCase
{
    public function test(): void
    {
        self::assertSame(
            ['foo' => 'bar', 'foobar' => 'bar'],
            createFieldValueComponentMap([['foo', 'bar'], ['foobar', 'bar']])
        );

        self::assertSame(['foo' => 'bar'], createFieldValueComponentMap([['foo', 'bar']]));

        self::assertSame(['foo' => 'bar'], createFieldValueComponentMap([['foo', 'bar'], ['foo', 'bar']]));

        self::assertNull(createFieldValueComponentMap([['foo', 'bar'], ['foo', 'baz']]));
    }
}
