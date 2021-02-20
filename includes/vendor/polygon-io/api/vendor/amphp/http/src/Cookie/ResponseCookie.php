<?php

namespace Amp\Http\Cookie;

/**
 * A cookie as sent in a response's 'set-cookie' header, so with attributes.
 *
 * This class does not deal with encoding of arbitrary names and values. If you want to use arbitrary values, please use
 * an encoding mechanism like Base64 or URL encoding.
 *
 * @link https://tools.ietf.org/html/rfc6265#section-5.2
 */
final class ResponseCookie
{
    private static $dateFormats = [
        'D, d M Y H:i:s T',
        'D, d-M-y H:i:s T',
        'D, d-M-Y H:i:s T',
        'D, d-m-y H:i:s T',
        'D, d-m-Y H:i:s T',
        'D M j G:i:s Y',
        'D M d H:i:s Y T',
    ];

    /**
     * Parses a cookie from a 'set-cookie' header.
     *
     * @param string $string Valid 'set-cookie' header line.
     *
     * @return self|null Returns a `ResponseCookie` instance on success and `null` on failure.
     */
    public static function fromHeader(string $string): ?self
    {
        $parts = \array_map("trim", \explode(";", $string));
        $nameValue = \explode("=", \array_shift($parts), 2);

        if (\count($nameValue) !== 2) {
            return null;
        }

        list($name, $value) = $nameValue;

        $name = \trim($name);
        $value = \trim($value, " \t\n\r\0\x0B\"");

        if ($name === "") {
            return null;
        }

        // httpOnly must default to false for parsing
        $meta = CookieAttributes::empty();
        $unknownAttributes = [];

        foreach ($parts as $part) {
            $pieces = \array_map('trim', \explode('=', $part, 2));
            $key = \strtolower($pieces[0]);

            if (1 === \count($pieces)) {
                switch ($key) {
                    case 'secure':
                        $meta = $meta->withSecure();
                        break;

                    case 'httponly':
                        $meta = $meta->withHttpOnly();
                        break;

                    default:
                        $unknownAttributes[] = $part;
                        break;
                }
            } else {
                switch ($key) {
                    case 'expires':
                        $time = self::parseDate($pieces[1]);

                        if ($time === null) {
                            break; // break is correct, see https://tools.ietf.org/html/rfc6265#section-5.2.1
                        }

                        $meta = $meta->withExpiry($time);
                        break;

                    case 'max-age':
                        $maxAge = \trim($pieces[1]);

                        // This also allows +1.42, but avoids a more complicated manual check
                        if (!\is_numeric($maxAge)) {
                            break; // break is correct, see https://tools.ietf.org/html/rfc6265#section-5.2.2
                        }

                        $meta = $meta->withMaxAge($maxAge);
                        break;

                    case 'path':
                        $meta = $meta->withPath($pieces[1]);
                        break;

                    case 'domain':
                        $meta = $meta->withDomain($pieces[1]);
                        break;

                    case 'samesite':
                        $normalizedValue = \ucfirst(\strtolower($pieces[1]));
                        if (!\in_array($normalizedValue, [
                            CookieAttributes::SAMESITE_NONE,
                            CookieAttributes::SAMESITE_LAX,
                            CookieAttributes::SAMESITE_STRICT,
                        ], true)) {
                            $unknownAttributes[] = $part;
                        } else {
                            $meta = $meta->withSameSite($normalizedValue);
                        }

                        break;

                    default:
                        $unknownAttributes[] = $part;
                        break;
                }
            }
        }

        try {
            $cookie = new self($name, $value, $meta);
            $cookie->unknownAttributes = $unknownAttributes;

            return $cookie;
        } catch (InvalidCookieException $e) {
            return null;
        }
    }

    /**
     * @param string $date Formatted cookie date
     *
     * @return \DateTimeImmutable|null Parsed date.
     */
    private static function parseDate(string $date): ?\DateTimeImmutable
    {
        foreach (self::$dateFormats as $dateFormat) {
            if ($parsedDate = \DateTimeImmutable::createFromFormat($dateFormat, $date, new \DateTimeZone('GMT'))) {
                return $parsedDate;
            }
        }

        return null;
    }

    /** @var string[] */
    private $unknownAttributes = [];
    /** @var string */
    private $name;
    /** @var string */
    private $value;
    /** @var CookieAttributes */
    private $attributes;

    /**
     * @param string           $name Name of the cookie.
     * @param string           $value Value of the cookie.
     * @param CookieAttributes $attributes Attributes of the cookie.
     *
     * @throws InvalidCookieException If name or value is invalid.
     */
    public function __construct(
        string $name,
        string $value = '',
        CookieAttributes $attributes = null
    ) {
        if (!\preg_match('(^[^()<>@,;:\\\"/[\]?={}\x01-\x20\x7F]++$)', $name)) {
            throw new InvalidCookieException("Invalid cookie name: '{$name}'");
        }

        if (!\preg_match('(^[\x21\x23-\x2B\x2D-\x3A\x3C-\x5B\x5D-\x7E]*+$)', $value)) {
            throw new InvalidCookieException("Invalid cookie value: '{$value}'");
        }

        $this->name = $name;
        $this->value = $value;
        $this->attributes = $attributes ?? CookieAttributes::default();
    }

    /**
     * @return string Name of the cookie.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function withName(string $name): self
    {
        if (!\preg_match('(^[^()<>@,;:\\\"/[\]?={}\x01-\x20\x7F]++$)', $name)) {
            throw new InvalidCookieException("Invalid cookie name: '{$name}'");
        }

        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    /**
     * @return string Value of the cookie.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function withValue(string $value): self
    {
        if (!\preg_match('(^[\x21\x23-\x2B\x2D-\x3A\x3C-\x5B\x5D-\x7E]*+$)', $value)) {
            throw new InvalidCookieException("Invalid cookie value: '{$value}'");
        }

        $clone = clone $this;
        $clone->value = $value;

        return $clone;
    }

    /**
     * @return \DateTimeImmutable|null Expiry if set, otherwise `null`.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.1
     */
    public function getExpiry(): ?\DateTimeImmutable
    {
        return $this->attributes->getExpiry();
    }

    public function withExpiry(\DateTimeInterface $expiry): self
    {
        return $this->withAttributes($this->attributes->withExpiry($expiry));
    }

    public function withoutExpiry(): self
    {
        return $this->withAttributes($this->attributes->withoutExpiry());
    }

    /**
     * @return int|null Max-Age if set, otherwise `null`.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.2
     */
    public function getMaxAge(): ?int
    {
        return $this->attributes->getMaxAge();
    }

    public function withMaxAge(int $maxAge): self
    {
        return $this->withAttributes($this->attributes->withMaxAge($maxAge));
    }

    public function withoutMaxAge(): self
    {
        return $this->withAttributes($this->attributes->withoutMaxAge());
    }

    /**
     * @return string Cookie path.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.4
     */
    public function getPath(): string
    {
        return $this->attributes->getPath();
    }

    public function withPath(string $path): self
    {
        return $this->withAttributes($this->attributes->withPath($path));
    }

    /**
     * @return string Cookie domain.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.3
     */
    public function getDomain(): string
    {
        return $this->attributes->getDomain();
    }

    public function withDomain(string $domain): self
    {
        return $this->withAttributes($this->attributes->withDomain($domain));
    }

    /**
     * @return bool Whether the secure flag is enabled or not.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.5
     */
    public function isSecure(): bool
    {
        return $this->attributes->isSecure();
    }

    public function withSecure(): self
    {
        return $this->withAttributes($this->attributes->withSecure());
    }

    public function withoutSecure(): self
    {
        return $this->withAttributes($this->attributes->withoutSecure());
    }

    /**
     * @return bool Whether the httpOnly flag is enabled or not.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.6
     */
    public function isHttpOnly(): bool
    {
        return $this->attributes->isHttpOnly();
    }

    public function withHttpOnly(): self
    {
        return $this->withAttributes($this->attributes->withHttpOnly());
    }

    public function withoutHttpOnly(): self
    {
        return $this->withAttributes($this->attributes->withoutHttpOnly());
    }

    public function withSameSite(string $sameSite): self
    {
        return $this->withAttributes($this->attributes->withSameSite($sameSite));
    }

    public function withoutSameSite(): self
    {
        return $this->withAttributes($this->attributes->withoutSameSite());
    }

    public function getSameSite(): ?string
    {
        return $this->attributes->getSameSite();
    }

    /**
     * @return CookieAttributes All cookie attributes.
     */
    public function getAttributes(): CookieAttributes
    {
        return $this->attributes;
    }

    public function withAttributes(CookieAttributes $attributes): self
    {
        $clone = clone $this;
        $clone->attributes = $attributes;

        return $clone;
    }

    /**
     * @return string Representation of the cookie as in a 'set-cookie' header.
     */
    public function __toString(): string
    {
        $line = $this->name . '=' . $this->value;
        $line .= $this->attributes;

        $unknownAttributes = \implode('; ', $this->unknownAttributes);
        if ($unknownAttributes !== '') {
            $line .= '; ' . $unknownAttributes;
        }

        return $line;
    }
}
