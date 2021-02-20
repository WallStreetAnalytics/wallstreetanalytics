<?php

namespace Amp\Http\Cookie;

use PHPUnit\Framework\TestCase;

class ResponseCookieTest extends TestCase
{
    public function testParsingOnEmptyName()
    {
        $this->assertNull(ResponseCookie::fromHeader("=123438afes7a8"));
    }

    public function testParsingOnInvalidNameValueCount()
    {
        $this->assertNull(ResponseCookie::fromHeader("; HttpOnly=123"));
    }

    public function testParsing()
    {
        // Examples from https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie
        $this->assertEquals(
            new ResponseCookie("sessionid", "38afes7a8", CookieAttributes::empty()->withHttpOnly()->withPath("/")),
            ResponseCookie::fromHeader("sessionid=38afes7a8; HttpOnly; Path=/")
        );

        $expectedMeta = CookieAttributes::empty()
            ->withHttpOnly()
            ->withSecure()
            ->withExpiry(new \DateTimeImmutable("Wed, 21 Oct 2015 07:28:00", new \DateTimeZone("GMT")));

        $this->assertEquals(
            new ResponseCookie("id", "a3fWa", $expectedMeta),
            ResponseCookie::fromHeader("id=a3fWa; Expires=Wed, 21 Oct 2015 07:28:00 GMT; Secure; HttpOnly")
        );

        // This might fail if the second switches between withMaxAge() and fromHeader() - we take the risk
        $expectedMeta = CookieAttributes::empty()
            ->withMaxAge(60);

        $this->assertEquals(
            new ResponseCookie("id", "a3fWa", $expectedMeta),
            ResponseCookie::fromHeader("id=a3fWa; Max-AGE=60")
        );

        // Missing "Wed, " in date, so date is ignored
        $expectedMeta = CookieAttributes::empty()
            ->withDomain("example.com")
            ->withPath("/");

        $this->assertEquals(
            new ResponseCookie("qwerty", "219ffwef9w0f", $expectedMeta),
            ResponseCookie::fromHeader("qwerty=219ffwef9w0f; Domain=example.com; Path=/; Expires=30 Aug 2019 00:00:00 GMT")
        );

        $expectedMeta = CookieAttributes::empty()
            ->withDomain("example.com")
            ->withPath("/")
            ->withExpiry(new \DateTimeImmutable("Wed, 30 Aug 2019 00:00:00", new \DateTimeZone("GMT")));

        $this->assertEquals(
            new ResponseCookie("qwerty", "219ffwef9w0f", $expectedMeta),
            $cookie = ResponseCookie::fromHeader("qwerty=219ffwef9w0f; Domain=example.com; Path=/; Expires=Wed, 30 Aug 2019 00:00:00 GMT")
        );

        $this->assertFalse($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertSame("qwerty", $cookie->getName());
        $this->assertSame("219ffwef9w0f", $cookie->getValue());
        $this->assertSame("example.com", $cookie->getDomain());
        $this->assertSame("/", $cookie->getPath());
        $this->assertSame(
            (new \DateTimeImmutable("Wed, 30 Aug 2019 00:00:00", new \DateTimeZone("GMT")))->getTimestamp(),
            $cookie->getExpiry()->getTimestamp()
        );

        // Non-digit in Max-Age
        $this->assertEquals(
            new ResponseCookie("qwerty", "219ffwef9w0f", CookieAttributes::empty()),
            ResponseCookie::fromHeader("qwerty=219ffwef9w0f; Max-Age=12520b")
        );

        // "-" in front in Max-Age
        $this->assertEquals(
            new ResponseCookie("qwerty", "219ffwef9w0f", CookieAttributes::empty()->withMaxAge(-1)),
            ResponseCookie::fromHeader("qwerty=219ffwef9w0f; Max-Age=-1")
        );

        $this->assertNull(
            ResponseCookie::fromHeader("query foo=129")
        );
    }

    public function testGetMaxAge()
    {
        $responseCookie = new ResponseCookie("qwerty", "219ffwef9w0f", CookieAttributes::empty()->withMaxAge(10));
        $this->assertSame(10, $responseCookie->getMaxAge());
    }

    public function testInvalidName()
    {
        $this->expectException(InvalidCookieException::class);

        new ResponseCookie("foo:bar");
    }

    public function testInvalidValue()
    {
        $this->expectException(InvalidCookieException::class);

        new ResponseCookie("foobar", "foo;bar");
    }

    public function testGetAttributes()
    {
        $attributes = CookieAttributes::default();
        $cookie = new ResponseCookie("foobar", "xxx", $attributes);

        $this->assertSame($attributes, $cookie->getAttributes());
    }

    public function testToString()
    {
        $attributes = CookieAttributes::default();
        $cookie = new ResponseCookie("foobar", "xxx", $attributes);

        $this->assertSame("foobar=xxx; HttpOnly", (string) $cookie);
    }

    public function testModifyName()
    {
        $cookie = new ResponseCookie("foobar", "what-is-this");
        $newCookie = $cookie->withName('bar');

        $this->assertSame('foobar', $cookie->getName());
        $this->assertSame('bar', $newCookie->getName());
    }

    public function testModifyValue()
    {
        $cookie = new ResponseCookie("foobar", "what-is-this");
        $newCookie = $cookie->withValue('what-is-that');

        $this->assertSame('what-is-this', $cookie->getValue());
        $this->assertSame('what-is-that', $newCookie->getValue());
    }

    public function testModifyHttpOnly()
    {
        $cookie = new ResponseCookie("foobar", "what-is-this");
        $newCookie = $cookie->withoutHttpOnly();

        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($newCookie->withHttpOnly()->isHttpOnly());
        $this->assertFalse($newCookie->isHttpOnly());
    }

    public function testModifySecure()
    {
        $cookie = new ResponseCookie("foobar", "what-is-this");
        $newCookie = $cookie->withSecure();

        $this->assertFalse($cookie->isSecure());
        $this->assertFalse($newCookie->withoutSecure()->isSecure());
        $this->assertTrue($newCookie->isSecure());
    }

    public function testModifyDomain()
    {
        $cookie = new ResponseCookie("foobar", "what-is-this");
        $newCookie = $cookie->withDomain('example.com');

        $this->assertSame('', $cookie->getDomain());
        $this->assertSame('example.com', $newCookie->getDomain());
    }

    public function testModifyPath()
    {
        $cookie = new ResponseCookie("foobar", "what-is-this");
        $newCookie = $cookie->withPath('/example');

        $this->assertSame('', $cookie->getPath());
        $this->assertSame('/example', $newCookie->getPath());
    }

    public function testModifyExpiry()
    {
        $cookie = new ResponseCookie("foobar", "what-is-this");
        $newCookie = $cookie->withExpiry(\DateTimeImmutable::createFromFormat('Y-m-d', '2019-06-10'));

        $this->assertNull($cookie->getExpiry());
        $this->assertNull($newCookie->withoutExpiry()->getExpiry());
        $this->assertSame('2019-06-10', $newCookie->getExpiry()->format('Y-m-d'));
    }

    public function testModifyExpiryMutable()
    {
        $cookie = new ResponseCookie("foobar", "what-is-this");
        $expiry = \DateTime::createFromFormat('Y-m-d', '2019-06-10');
        $newCookie = $cookie->withExpiry($expiry);

        $this->assertNull($cookie->getExpiry());
        $this->assertNull($newCookie->withoutExpiry()->getExpiry());
        $this->assertSame('2019-06-10', $newCookie->getExpiry()->format('Y-m-d'));

        $expiry->add(new \DateInterval('P2D'));
        $this->assertSame('2019-06-10', $newCookie->getExpiry()->format('Y-m-d'));
    }

    public function testModifyMaxAge()
    {
        $cookie = new ResponseCookie("foobar", "what-is-this");
        $newCookie = $cookie->withMaxAge(12);

        $this->assertNull($cookie->getMaxAge());
        $this->assertNull($newCookie->withoutMaxAge()->getMaxAge());
        $this->assertSame(12, $newCookie->getMaxAge());
    }

    public function testModifySameSite()
    {
        $cookie = new ResponseCookie("foobar", "what-is-this");
        $newCookie = $cookie->withSameSite('Lax');

        $this->assertNull($cookie->getSameSite());
        $this->assertNull($newCookie->withoutSameSite()->getSameSite());
        $this->assertSame('Lax', $newCookie->getSameSite());
    }

    public function testInvalidCookieName()
    {
        $this->expectException(InvalidCookieException::class);

        new ResponseCookie("foo bar");
    }

    public function testInvalidCookieNameModify()
    {
        $cookie = new ResponseCookie("foobar");

        $this->expectException(InvalidCookieException::class);

        $cookie->withName('foo bar');
    }

    public function testInvalidCookieValue()
    {
        $this->expectException(InvalidCookieException::class);

        new ResponseCookie("foobar", "what is this");
    }

    public function testInvalidCookieValueModify()
    {
        $cookie = new ResponseCookie("foobar", "what-is-this");

        $this->expectException(InvalidCookieException::class);

        $cookie->withValue('what is this');
    }

    public function testSameSiteInvalid()
    {
        $cookie = ResponseCookie::fromHeader('foo=bar; SameSite=lax');

        $this->assertSame('foo', $cookie->getName());
        $this->assertSame('bar', $cookie->getValue());
        $this->assertSame('Lax', $cookie->getSameSite());
    }

    public function testPreservesUnknownAttributes()
    {
        $cookie = ResponseCookie::fromHeader('key=value; HttpOnly; SameSite=strict;Foobar');
        $this->assertNotNull($cookie);
        $this->assertSame('key', $cookie->getName());
        $this->assertSame('value', $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('key=value; HttpOnly; SameSite=Strict; Foobar', (string) $cookie);
    }

    public function testPreservesUnknownAttributes_invalidSameSite()
    {
        $cookie = ResponseCookie::fromHeader('key=value; HttpOnly; SameSite=foo;Foobar; bla=x');
        $this->assertNotNull($cookie);
        $this->assertSame('key', $cookie->getName());
        $this->assertSame('value', $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('key=value; HttpOnly; SameSite=foo; Foobar; bla=x', (string) $cookie);
    }
}
