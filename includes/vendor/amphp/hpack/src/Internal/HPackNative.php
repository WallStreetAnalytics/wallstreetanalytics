<?php

namespace Amp\Http\Internal;

use Amp\Http\HPackException;

/** @internal */
final class HPackNative
{
    private const HUFFMAN_CODE = [
        /* 0x00 */ 0x1ff8, 0x7fffd8, 0xfffffe2, 0xfffffe3, 0xfffffe4, 0xfffffe5, 0xfffffe6, 0xfffffe7,
        /* 0x08 */ 0xfffffe8, 0xffffea, 0x3ffffffc, 0xfffffe9, 0xfffffea, 0x3ffffffd, 0xfffffeb, 0xfffffec,
        /* 0x10 */ 0xfffffed, 0xfffffee, 0xfffffef, 0xffffff0, 0xffffff1, 0xffffff2, 0x3ffffffe, 0xffffff3,
        /* 0x18 */ 0xffffff4, 0xffffff5, 0xffffff6, 0xffffff7, 0xffffff8, 0xffffff9, 0xffffffa, 0xffffffb,
        /* 0x20 */ 0x14, 0x3f8, 0x3f9, 0xffa, 0x1ff9, 0x15, 0xf8, 0x7fa,
        /* 0x28 */ 0x3fa, 0x3fb, 0xf9, 0x7fb, 0xfa, 0x16, 0x17, 0x18,
        /* 0x30 */ 0x0, 0x1, 0x2, 0x19, 0x1a, 0x1b, 0x1c, 0x1d,
        /* 0x38 */ 0x1e, 0x1f, 0x5c, 0xfb, 0x7ffc, 0x20, 0xffb, 0x3fc,
        /* 0x40 */ 0x1ffa, 0x21, 0x5d, 0x5e, 0x5f, 0x60, 0x61, 0x62,
        /* 0x48 */ 0x63, 0x64, 0x65, 0x66, 0x67, 0x68, 0x69, 0x6a,
        /* 0x50 */ 0x6b, 0x6c, 0x6d, 0x6e, 0x6f, 0x70, 0x71, 0x72,
        /* 0x58 */ 0xfc, 0x73, 0xfd, 0x1ffb, 0x7fff0, 0x1ffc, 0x3ffc, 0x22,
        /* 0x60 */ 0x7ffd, 0x3, 0x23, 0x4, 0x24, 0x5, 0x25, 0x26,
        /* 0x68 */ 0x27, 0x6, 0x74, 0x75, 0x28, 0x29, 0x2a, 0x7,
        /* 0x70 */ 0x2b, 0x76, 0x2c, 0x8, 0x9, 0x2d, 0x77, 0x78,
        /* 0x78 */ 0x79, 0x7a, 0x7b, 0x7ffe, 0x7fc, 0x3ffd, 0x1ffd, 0xffffffc,
        /* 0x80 */ 0xfffe6, 0x3fffd2, 0xfffe7, 0xfffe8, 0x3fffd3, 0x3fffd4, 0x3fffd5, 0x7fffd9,
        /* 0x88 */ 0x3fffd6, 0x7fffda, 0x7fffdb, 0x7fffdc, 0x7fffdd, 0x7fffde, 0xffffeb, 0x7fffdf,
        /* 0x90 */ 0xffffec, 0xffffed, 0x3fffd7, 0x7fffe0, 0xffffee, 0x7fffe1, 0x7fffe2, 0x7fffe3,
        /* 0x98 */ 0x7fffe4, 0x1fffdc, 0x3fffd8, 0x7fffe5, 0x3fffd9, 0x7fffe6, 0x7fffe7, 0xffffef,
        /* 0xA0 */ 0x3fffda, 0x1fffdd, 0xfffe9, 0x3fffdb, 0x3fffdc, 0x7fffe8, 0x7fffe9, 0x1fffde,
        /* 0xA8 */ 0x7fffea, 0x3fffdd, 0x3fffde, 0xfffff0, 0x1fffdf, 0x3fffdf, 0x7fffeb, 0x7fffec,
        /* 0xB0 */ 0x1fffe0, 0x1fffe1, 0x3fffe0, 0x1fffe2, 0x7fffed, 0x3fffe1, 0x7fffee, 0x7fffef,
        /* 0xB8 */ 0xfffea, 0x3fffe2, 0x3fffe3, 0x3fffe4, 0x7ffff0, 0x3fffe5, 0x3fffe6, 0x7ffff1,
        /* 0xC0 */ 0x3ffffe0, 0x3ffffe1, 0xfffeb, 0x7fff1, 0x3fffe7, 0x7ffff2, 0x3fffe8, 0x1ffffec,
        /* 0xC8 */ 0x3ffffe2, 0x3ffffe3, 0x3ffffe4, 0x7ffffde, 0x7ffffdf, 0x3ffffe5, 0xfffff1, 0x1ffffed,
        /* 0xD0 */ 0x7fff2, 0x1fffe3, 0x3ffffe6, 0x7ffffe0, 0x7ffffe1, 0x3ffffe7, 0x7ffffe2, 0xfffff2,
        /* 0xD8 */ 0x1fffe4, 0x1fffe5, 0x3ffffe8, 0x3ffffe9, 0xffffffd, 0x7ffffe3, 0x7ffffe4, 0x7ffffe5,
        /* 0xE0 */ 0xfffec, 0xfffff3, 0xfffed, 0x1fffe6, 0x3fffe9, 0x1fffe7, 0x1fffe8, 0x7ffff3,
        /* 0xE8 */ 0x3fffea, 0x3fffeb, 0x1ffffee, 0x1ffffef, 0xfffff4, 0xfffff5, 0x3ffffea, 0x7ffff4,
        /* 0xF0 */ 0x3ffffeb, 0x7ffffe6, 0x3ffffec, 0x3ffffed, 0x7ffffe7, 0x7ffffe8, 0x7ffffe9, 0x7ffffea,
        /* 0xF8 */ 0x7ffffeb, 0xffffffe, 0x7ffffec, 0x7ffffed, 0x7ffffee, 0x7ffffef, 0x7fffff0, 0x3ffffee,
        /* end! */ 0x3fffffff
    ];

    private const HUFFMAN_CODE_LENGTHS = [
        /* 0x00 */ 13, 23, 28, 28, 28, 28, 28, 28,
        /* 0x08 */ 28, 24, 30, 28, 28, 30, 28, 28,
        /* 0x10 */ 28, 28, 28, 28, 28, 28, 30, 28,
        /* 0x18 */ 28, 28, 28, 28, 28, 28, 28, 28,
        /* 0x20 */ 6, 10, 10, 12, 13, 6, 8, 11,
        /* 0x28 */ 10, 10, 8, 11, 8, 6, 6, 6,
        /* 0x30 */ 5, 5, 5, 6, 6, 6, 6, 6,
        /* 0x38 */ 6, 6, 7, 8, 15, 6, 12, 10,
        /* 0x40 */ 13, 6, 7, 7, 7, 7, 7, 7,
        /* 0x48 */ 7, 7, 7, 7, 7, 7, 7, 7,
        /* 0x50 */ 7, 7, 7, 7, 7, 7, 7, 7,
        /* 0x58 */ 8, 7, 8, 13, 19, 13, 14, 6,
        /* 0x60 */ 15, 5, 6, 5, 6, 5, 6, 6,
        /* 0x68 */ 6, 5, 7, 7, 6, 6, 6, 5,
        /* 0x70 */ 6, 7, 6, 5, 5, 6, 7, 7,
        /* 0x78 */ 7, 7, 7, 15, 11, 14, 13, 28,
        /* 0x80 */ 20, 22, 20, 20, 22, 22, 22, 23,
        /* 0x88 */ 22, 23, 23, 23, 23, 23, 24, 23,
        /* 0x90 */ 24, 24, 22, 23, 24, 23, 23, 23,
        /* 0x98 */ 23, 21, 22, 23, 22, 23, 23, 24,
        /* 0xA0 */ 22, 21, 20, 22, 22, 23, 23, 21,
        /* 0xA8 */ 23, 22, 22, 24, 21, 22, 23, 23,
        /* 0xB0 */ 21, 21, 22, 21, 23, 22, 23, 23,
        /* 0xB8 */ 20, 22, 22, 22, 23, 22, 22, 23,
        /* 0xC0 */ 26, 26, 20, 19, 22, 23, 22, 25,
        /* 0xC8 */ 26, 26, 26, 27, 27, 26, 24, 25,
        /* 0xD0 */ 19, 21, 26, 27, 27, 26, 27, 24,
        /* 0xD8 */ 21, 21, 26, 26, 28, 27, 27, 27,
        /* 0xE0 */ 20, 24, 20, 21, 22, 21, 21, 23,
        /* 0xE8 */ 22, 22, 25, 25, 24, 24, 26, 23,
        /* 0xF0 */ 26, 27, 26, 26, 27, 27, 27, 27,
        /* 0xF8 */ 27, 28, 27, 27, 27, 27, 27, 26,
        /* end! */ 30
    ];

    private const DEFAULT_COMPRESSION_THRESHOLD = 1024;
    private const DEFAULT_MAX_SIZE = 4096;

    private static $huffmanLookup;
    private static $huffmanCodes;
    private static $huffmanLengths;

    private static $indexMap = [];

    /** @var string[][] */
    private $headers = [];

    /** @var int */
    private $hardMaxSize = self::DEFAULT_MAX_SIZE;

    /** @var int Max table size. */
    private $currentMaxSize = self::DEFAULT_MAX_SIZE;

    /** @var int Current table size. */
    private $size = 0;

    /** Called via bindTo(), see end of file */
    private static function init() /* : void */
    {
        self::$huffmanLookup = self::huffmanLookupInit();
        self::$huffmanCodes = self::huffmanCodesInit();
        self::$huffmanLengths = self::huffmanLengthsInit();

        foreach (\array_column(self::TABLE, 0) as $index => $name) {
            if (isset(self::$indexMap[$name])) {
                continue;
            }

            self::$indexMap[$name] = $index + 1;
        }
    }

    // (micro-)optimized decode
    private static function huffmanLookupInit(): array
    {
        if (('cli' !== \PHP_SAPI && 'phpdbg' !== \PHP_SAPI) || \filter_var(\ini_get('opcache.enable_cli'), \FILTER_VALIDATE_BOOLEAN)) {
            return require __DIR__ . '/huffman-lookup.php';
        }

        \gc_disable();
        $encodingAccess = [];
        $terminals = [];
        $index = 7;

        foreach (self::HUFFMAN_CODE as $chr => $bits) {
            $len = self::HUFFMAN_CODE_LENGTHS[$chr];

            for ($bit = 0; $bit < 8; $bit++) {
                $offlen = $len + $bit;
                $next = $bit;

                for ($byte = ($offlen - 1) >> 3; $byte > 0; $byte--) {
                    $cur = \str_pad(\decbin(($bits >> ($byte * 8 - ((0x30 - $offlen) & 7))) & 0xFF), 8, "0", STR_PAD_LEFT);
                    if (($encodingAccess[$next][$cur][0] ?? 0) !== 0) {
                        $next = $encodingAccess[$next][$cur][0];
                    } else {
                        $encodingAccess[$next][$cur] = [++$index, null];
                        $next = $index;
                    }
                }

                $key = \str_pad(
                    \decbin($bits & ((1 << ((($offlen - 1) & 7) + 1)) - 1)),
                    (($offlen - 1) & 7) + 1,
                    "0",
                    STR_PAD_LEFT
                );
                $encodingAccess[$next][$key] = [null, $chr > 0xFF ? "" : \chr($chr)];

                if ($offlen & 7) {
                    $terminals[$offlen & 7][] = [$key, $next];
                } else {
                    $encodingAccess[$next][$key][0] = 0;
                }
            }
        }

        $memoize = [];
        for ($off = 7; $off > 0; $off--) {
            foreach ($terminals[$off] as [$key, $next]) {
                if ($encodingAccess[$next][$key][0] === null) {
                    foreach ($encodingAccess[$off] as $chr => $cur) {
                        $encodingAccess[$next][($memoize[$key] ?? $memoize[$key] = \str_pad($key, 8, "0", STR_PAD_RIGHT)) | $chr] =
                            [$cur[0], $encodingAccess[$next][$key][1] != "" ? $encodingAccess[$next][$key][1] . $cur[1] : ""];
                    }

                    unset($encodingAccess[$next][$key]);
                }
            }
        }

        $memoize = [];
        for ($off = 7; $off > 0; $off--) {
            foreach ($terminals[$off] as [$key, $next]) {
                foreach ($encodingAccess[$next] as $k => $v) {
                    if (\strlen($k) !== 1) {
                        $encodingAccess[$next][$memoize[$k] ?? $memoize[$k] = \chr(\bindec($k))] = $v;
                        unset($encodingAccess[$next][$k]);
                    }
                }
            }

            unset($encodingAccess[$off]);
        }

        \gc_enable();

        return $encodingAccess;
    }

    /**
     * @param string $input
     *
     * @return string|null Returns null if decoding fails.
     */
    public static function huffmanDecode(string $input) /* : ?string */
    {
        $huffmanLookup = self::$huffmanLookup;
        $lookup = 0;
        $lengths = self::$huffmanLengths;
        $length = \strlen($input);
        $out = \str_repeat("\0", $length / 5 * 8 + 1); // max length

        // Fail if EOS symbol is found.
        if (\strpos($input, "\x3f\xff\xff\xff") !== false) {
            return null;
        }

        for ($bitCount = $off = $i = 0; $i < $length; $i++) {
            [$lookup, $chr] = $huffmanLookup[$lookup][$input[$i]];

            if ($chr === null) {
                continue;
            }

            if ($chr === "") {
                return null;
            }

            $out[$off++] = $chr[0];
            $bitCount += $lengths[$chr[0]];

            if (isset($chr[1])) {
                $out[$off++] = $chr[1];
                $bitCount += $lengths[$chr[1]];
            }
        }

        // Padding longer than 7-bits
        if ($i && $chr === null) {
            return null;
        }

        // Check for 0's in padding
        if ($bitCount & 7) {
            $mask = 0xff >> ($bitCount & 7);
            if ((\ord($input[$i - 1]) & $mask) !== $mask) {
                return null;
            }
        }

        return \substr($out, 0, $off);
    }

    private static function huffmanCodesInit(): array
    {
        if (('cli' !== \PHP_SAPI && 'phpdbg' !== \PHP_SAPI) || \filter_var(\ini_get('opcache.enable_cli'), \FILTER_VALIDATE_BOOLEAN)) {
            return require __DIR__ . '/huffman-codes.php';
        }

        $lookup = [];

        for ($chr = 0; $chr <= 0xFF; $chr++) {
            $bits = self::HUFFMAN_CODE[$chr];
            $length = self::HUFFMAN_CODE_LENGTHS[$chr];

            for ($bit = 0; $bit < 8; $bit++) {
                $bytes = ($length + $bit - 1) >> 3;
                $codes = [];

                for ($byte = $bytes; $byte >= 0; $byte--) {
                    $codes[] = \chr(
                        $byte
                            ? $bits >> ($length - ($bytes - $byte + 1) * 8 + $bit)
                            : ($bits << ((0x30 - $length - $bit) & 7))
                    );
                }

                $lookup[$bit][\chr($chr)] = $codes;
            }
        }

        return $lookup;
    }

    private static function huffmanLengthsInit(): array
    {
        $lengths = [];

        for ($chr = 0; $chr <= 0xFF; $chr++) {
            $lengths[\chr($chr)] = self::HUFFMAN_CODE_LENGTHS[$chr];
        }

        return $lengths;
    }

    public static function huffmanEncode(string $input): string
    {
        $codes = self::$huffmanCodes;
        $lengths = self::$huffmanLengths;

        $length = \strlen($input);
        $out = \str_repeat("\0", $length * 5 + 1); // max length

        for ($bitCount = $i = 0; $i < $length; $i++) {
            $chr = $input[$i];
            $byte = $bitCount >> 3;

            foreach ($codes[$bitCount & 7][$chr] as $bits) {
                // Note: |= can't be used with strings in PHP
                $out[$byte] = $out[$byte] | $bits;
                $byte++;
            }

            $bitCount += $lengths[$chr];
        }

        if ($bitCount & 7) {
            // Note: |= can't be used with strings in PHP
            $out[$byte - 1] = $out[$byte - 1] | \chr(0xFF >> ($bitCount & 7));
        }

        return $i ? \substr($out, 0, $byte) : '';
    }

    /** @see RFC 7541 Appendix A */
    const LAST_INDEX = 61;
    const TABLE = [ // starts at 1
        [":authority", ""],
        [":method", "GET"],
        [":method", "POST"],
        [":path", "/"],
        [":path", "/index.html"],
        [":scheme", "http"],
        [":scheme", "https"],
        [":status", "200"],
        [":status", "204"],
        [":status", "206"],
        [":status", "304"],
        [":status", "400"],
        [":status", "404"],
        [":status", "500"],
        ["accept-charset", ""],
        ["accept-encoding", "gzip, deflate"],
        ["accept-language", ""],
        ["accept-ranges", ""],
        ["accept", ""],
        ["access-control-allow-origin", ""],
        ["age", ""],
        ["allow", ""],
        ["authorization", ""],
        ["cache-control", ""],
        ["content-disposition", ""],
        ["content-encoding", ""],
        ["content-language", ""],
        ["content-length", ""],
        ["content-location", ""],
        ["content-range", ""],
        ["content-type", ""],
        ["cookie", ""],
        ["date", ""],
        ["etag", ""],
        ["expect", ""],
        ["expires", ""],
        ["from", ""],
        ["host", ""],
        ["if-match", ""],
        ["if-modified-since", ""],
        ["if-none-match", ""],
        ["if-range", ""],
        ["if-unmodified-since", ""],
        ["last-modified", ""],
        ["link", ""],
        ["location", ""],
        ["max-forwards", ""],
        ["proxy-authentication", ""],
        ["proxy-authorization", ""],
        ["range", ""],
        ["referer", ""],
        ["refresh", ""],
        ["retry-after", ""],
        ["server", ""],
        ["set-cookie", ""],
        ["strict-transport-security", ""],
        ["transfer-encoding", ""],
        ["user-agent", ""],
        ["vary", ""],
        ["via", ""],
        ["www-authenticate", ""]
    ];

    private static function decodeDynamicInteger(string $input, int &$off): int
    {
        if (!isset($input[$off])) {
            throw new HPackException('Invalid input data, too short for dynamic integer');
        }

        $c = \ord($input[$off++]);
        $int = $c & 0x7f;
        $i = 0;

        while ($c & 0x80) {
            if (!isset($input[$off])) {
                return -0x80;
            }

            $c = \ord($input[$off++]);
            $int += ($c & 0x7f) << (++$i * 7);
        }

        return $int;
    }

    /**
     * @param int $maxSize Upper limit on table size.
     */
    public function __construct(int $maxSize = self::DEFAULT_MAX_SIZE)
    {
        $this->hardMaxSize = $maxSize;
    }

    /**
     * Sets the upper limit on table size. Dynamic table updates requesting a size above this size will result in a
     * decoding error (i.e., returning null from decode()).
     *
     * @param int $maxSize
     */
    public function setTableSizeLimit(int $maxSize) /* : void */
    {
        $this->hardMaxSize = $maxSize;
    }

    /**
     * Resizes the table to the given size, removing old entries as per section 4.4 if necessary.
     *
     * @param int|null $size
     */
    public function resizeTable(int $size = null) /* : void */
    {
        if ($size !== null) {
            $this->currentMaxSize = \max(0, \min($size, $this->hardMaxSize));
        }

        while ($this->size > $this->currentMaxSize) {
            [$name, $value] = \array_pop($this->headers);
            $this->size -= 32 + \strlen($name) + \strlen($value);
        }
    }

    /**
     * @param string $input Encoded headers.
     * @param int $maxSize Maximum length of the decoded header string.
     *
     * @return string[][]|null Returns null if decoding fails or if $maxSize is exceeded.
     */
    public function decode(string $input, int $maxSize) /* : ?array */
    {
        $headers = [];
        $off = 0;
        $inputLength = \strlen($input);
        $size = 0;

        try {
            // dynamic $table as per 2.3.2
            while ($off < $inputLength) {
                $index = \ord($input[$off++]);

                if ($index & 0x80) {
                    // range check
                    if ($index <= self::LAST_INDEX + 0x80) {
                        if ($index === 0x80) {
                            return null;
                        }

                        [$name, $value] = $headers[] = self::TABLE[$index - 0x81];
                    } else {
                        if ($index == 0xff) {
                            $index = self::decodeDynamicInteger($input, $off) + 0xff;
                        }

                        $index -= 0x81 + self::LAST_INDEX;
                        if (!isset($this->headers[$index])) {
                            return null;
                        }

                        [$name, $value] = $headers[] = $this->headers[$index];
                    }
                } elseif (($index & 0x60) !== 0x20) { // (($index & 0x40) || !($index & 0x20)): bit 4: never index is ignored
                    $dynamic = (bool) ($index & 0x40);

                    if ($index & ($dynamic ? 0x3f : 0x0f)) { // separate length
                        if ($dynamic) {
                            if ($index === 0x7f) {
                                $index = self::decodeDynamicInteger($input, $off) + 0x3f;
                            } else {
                                $index &= 0x3f;
                            }
                        } else {
                            $index &= 0x0f;
                            if ($index === 0x0f) {
                                $index = self::decodeDynamicInteger($input, $off) + 0x0f;
                            }
                        }

                        if ($index < 0) {
                            return null;
                        }

                        if ($index <= self::LAST_INDEX) {
                            $header = self::TABLE[$index - 1];
                        } elseif (!isset($this->headers[$index - 1 - self::LAST_INDEX])) {
                            return null;
                        } else {
                            $header = $this->headers[$index - 1 - self::LAST_INDEX];
                        }
                    } else {
                        if ($off >= $inputLength) {
                            return null;
                        }

                        $length = \ord($input[$off++]);
                        $huffman = $length & 0x80;
                        $length &= 0x7f;

                        if ($length === 0x7f) {
                            $length = self::decodeDynamicInteger($input, $off) + 0x7f;
                        }

                        if ($inputLength - $off < $length || $length <= 0) {
                            return null;
                        }

                        if ($huffman) {
                            $header = [self::huffmanDecode(\substr($input, $off, $length))];
                            if ($header[0] === null) {
                                return null;
                            }
                        } else {
                            $header = [\substr($input, $off, $length)];
                        }

                        $off += $length;
                    }

                    if ($off >= $inputLength) {
                        return null;
                    }

                    $length = \ord($input[$off++]);
                    $huffman = $length & 0x80;
                    $length &= 0x7f;

                    if ($length === 0x7f) {
                        $length = self::decodeDynamicInteger($input, $off) + 0x7f;
                    }

                    if ($inputLength - $off < $length || $length < 0) {
                        return null;
                    }

                    if ($huffman) {
                        $header[1] = self::huffmanDecode(\substr($input, $off, $length));
                        if ($header[1] === null) {
                            return null;
                        }
                    } else {
                        $header[1] = \substr($input, $off, $length);
                    }

                    $off += $length;

                    if ($dynamic) {
                        \array_unshift($this->headers, $header);
                        $this->size += 32 + \strlen($header[0]) + \strlen($header[1]);
                        if ($this->currentMaxSize < $this->size) {
                            $this->resizeTable();
                        }
                    }

                    [$name, $value] = $headers[] = $header;
                } else { // if ($index & 0x20) {
                    if ($off >= $inputLength) {
                        return null; // Dynamic table size update must not be the last entry in header block.
                    }

                    $index &= 0x1f;
                    if ($index === 0x1f) {
                        $index = self::decodeDynamicInteger($input, $off) + 0x1f;
                    }

                    if ($index > $this->hardMaxSize) {
                        return null;
                    }

                    $this->resizeTable($index);

                    continue;
                }

                $size += \strlen($name) + \strlen($value);

                if ($size > $maxSize) {
                    return null;
                }
            }
        } catch (HPackException $e) {
            return null;
        }

        return $headers;
    }

    private static function encodeDynamicInteger(int $int): string
    {
        $out = "";
        for ($i = 0; ($int >> $i) > 0x80; $i += 7) {
            $out .= \chr(0x80 | (($int >> $i) & 0x7f));
        }
        return $out . \chr($int >> $i);
    }

    /**
     * @param string[][] $headers
     * @param int $compressionThreshold Compress strings whose length is at least the number of bytes given.
     *
     * @return string
     */
    public function encode(array $headers, int $compressionThreshold = self::DEFAULT_COMPRESSION_THRESHOLD): string
    {
        // @TODO implementation is deliberately primitive... [doesn't use any dynamic table...]
        $output = "";

        foreach ($headers as [$name, $value]) {
            if (isset(self::$indexMap[$name])) {
                $index = self::$indexMap[$name];
                if ($index < 0x10) {
                    $output .= \chr($index);
                } else {
                    $output .= "\x0f" . \chr($index - 0x0f);
                }
            } else {
                $output .= "\0" . $this->encodeString($name, $compressionThreshold);
            }

            $output .= $this->encodeString($value, $compressionThreshold);
        }

        return $output;
    }

    private function encodeString(string $value, int $compressionThreshold): string
    {
        $prefix = "\0";
        if (\strlen($value) >= $compressionThreshold) {
            $value = self::huffmanEncode($value);
            $prefix = "\x80";
        }

        if (\strlen($value) < 0x7f) {
            return ($prefix | \chr(\strlen($value))) . $value;
        }

        return ($prefix | "\x7f") . self::encodeDynamicInteger(\strlen($value) - 0x7f) . $value;
    }
}

(function () {
    static::init();
})->bindTo(null, HPackNative::class)();
