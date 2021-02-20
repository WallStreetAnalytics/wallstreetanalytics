<?php

/** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedClassInspection */

namespace Amp\Http\Internal;

use Amp\Http\HPackException;
use FFI;

/** @internal */
final class HPackNghttp2
{
    private const FLAG_NO_INDEX = 0x01;
    private const FLAG_NO_COPY_NAME = 0x02;
    private const FLAG_NO_COPY_VALUE = 0x04;
    private const FLAG_NO_COPY = self::FLAG_NO_COPY_NAME | self::FLAG_NO_COPY_VALUE;
    private const FLAG_NO_COPY_SENSITIVE = self::FLAG_NO_COPY | self::FLAG_NO_INDEX;
    private const SENSITIVE_HEADERS = [
        'authorization' => self::FLAG_NO_COPY_SENSITIVE,
        'cookie' => self::FLAG_NO_COPY_SENSITIVE,
        'proxy-authorization' => self::FLAG_NO_COPY_SENSITIVE,
        'set-cookie' => self::FLAG_NO_COPY_SENSITIVE,
    ];

    private static $ffi;
    private static $deflatePtrType;
    private static $inflatePtrType;
    private static $nvType;
    private static $nvSize;
    private static $charType;
    private static $uint8Type;
    private static $uint8PtrType;
    private static $decodeNv;
    private static $decodeNvPtr;
    private static $decodeFlags;
    private static $decodeFlagsPtr;
    private static $supported;

    public static function isSupported(): bool
    {
        if (isset(self::$supported)) {
            return self::$supported;
        }

        if (!\extension_loaded('ffi')) {
            return self::$supported = false;
        }

        if (!\class_exists(FFI::class)) {
            return self::$supported = false;
        }

        try {
            self::init();

            return self::$supported = true;
        } catch (\Throwable $e) {
            return self::$supported = false;
        }
    }

    private static function init(): void
    {
        if (self::$ffi) {
            return;
        }

        $header = \file_get_contents(__DIR__ . '/amp-hpack.h');

        try {
            self::$ffi = FFI::cdef($header, 'libnghttp2.so');
        } catch (\Throwable $exception) {
            self::$ffi = FFI::cdef($header, 'libnghttp2.dylib');
        }

        self::$deflatePtrType = self::$ffi->type('nghttp2_hd_deflater*');
        self::$inflatePtrType = self::$ffi->type('nghttp2_hd_inflater*');
        self::$nvType = self::$ffi->type('nghttp2_nv');
        self::$nvSize = FFI::sizeof(self::$nvType);
        self::$charType = self::$ffi->type('char');
        self::$uint8Type = self::$ffi->type('uint8_t');
        self::$uint8PtrType = self::$ffi->type('uint8_t*');

        self::$decodeNv = self::$ffi->new(self::$nvType);
        self::$decodeNvPtr = FFI::addr(self::$decodeNv);

        self::$decodeFlags = self::$ffi->new('int');
        self::$decodeFlagsPtr = FFI::addr(self::$decodeFlags);
    }

    private static function createBufferFromString(string $value)
    {
        $length = \strlen($value);

        $buffer = FFI::new(FFI::arrayType(self::$uint8Type, [$length]));
        FFI::memcpy($buffer, $value, $length);

        return $buffer;
    }

    private $deflatePtr;
    private $inflatePtr;

    /**
     * @param int $maxSize Upper limit on table size.
     */
    public function __construct(int $maxSize = 4096)
    {
        self::init();

        $this->deflatePtr = self::$ffi->new(self::$deflatePtrType);
        $this->inflatePtr = self::$ffi->new(self::$inflatePtrType);

        $return = self::$ffi->nghttp2_hd_deflate_new(FFI::addr($this->deflatePtr), $maxSize);
        if ($return !== 0) {
            throw new \RuntimeException('Failed to init deflate context');
        }

        $return = self::$ffi->nghttp2_hd_inflate_new(FFI::addr($this->inflatePtr));
        if ($return !== 0) {
            throw new \RuntimeException('Failed to init inflate context');
        }
    }

    /**
     * @param string $input Encoded headers.
     * @param int    $maxSize Maximum length of the decoded header string.
     *
     * @return string[][]|null Returns null if decoding fails or if $maxSize is exceeded.
     */
    public function decode(string $input, int $maxSize): ?array
    {
        $ffi = self::$ffi;
        $pair = self::$decodeNv;
        $pairPtr = self::$decodeNvPtr;
        $flags = self::$decodeFlags;
        $flagsPtr = self::$decodeFlagsPtr;
        $inflate = $this->inflatePtr;

        $size = 0;

        $bufferLength = \strlen($input);
        $buffer = self::createBufferFromString($input);

        $offset = 0;
        $bufferPtr = FFI::cast(self::$uint8PtrType, $buffer);

        $headers = [];

        while (true) {
            $read = $ffi->nghttp2_hd_inflate_hd2($inflate, $pairPtr, $flagsPtr, $bufferPtr, $bufferLength - $offset, 1);

            if ($read < 0) {
                return null;
            }

            $offset += $read;
            $bufferPtr += $read;

            $cFlags = $flags->cdata;
            if ($cFlags & 0x02) { // NGHTTP2_HD_INFLATE_EMIT
                $nameLength = $pair->namelen;
                $valueLength = $pair->valuelen;

                $headers[] = [
                    FFI::string($pair->name, $nameLength),
                    FFI::string($pair->value, $valueLength),
                ];

                $size += $nameLength + $valueLength;

                if ($size > $maxSize) {
                    return null;
                }
            }

            if ($cFlags & 0x01) { // NGHTTP2_HD_INFLATE_FINAL
                $ffi->nghttp2_hd_inflate_end_headers($inflate);

                FFI::memset($pair, 0, self::$nvSize);

                return $headers;
            }

            if ($read === 0 || $offset > $bufferLength) {
                return null;
            }
        }

        return null;
    }

    /**
     * @param string[][] $headers
     *
     * @return string Encoded headers.
     */
    public function encode(array $headers): string
    {
        $ffi = self::$ffi;

        // To keep memory buffers
        $buffers = [];

        $headerCount = \count($headers);
        $current = 0;

        $pairs = $ffi->new(FFI::arrayType(self::$nvType, [$headerCount]));

        foreach ($headers as $index => [$name, $value]) {
            \assert($index === $current);

            $pair = $pairs[$current];

            $nameBuffer = self::createBufferFromString($name);
            $valueBuffer = self::createBufferFromString($value);

            $pair->name = FFI::cast(self::$uint8PtrType, $nameBuffer);
            $pair->namelen = \strlen($name);

            $pair->value = FFI::cast(self::$uint8PtrType, $valueBuffer);
            $pair->valuelen = \strlen($value);

            $pair->flags = self::SENSITIVE_HEADERS[$name] ?? self::FLAG_NO_COPY;

            $buffers[] = $nameBuffer;
            $buffers[] = $valueBuffer;

            $current++;
        }

        $bufferLength = $ffi->nghttp2_hd_deflate_bound($this->deflatePtr, $pairs, $headerCount);
        $buffer = FFI::new(FFI::arrayType(self::$uint8Type, [$bufferLength]));

        $bufferLength = $ffi->nghttp2_hd_deflate_hd($this->deflatePtr, $buffer, $bufferLength, $pairs, $headerCount);

        if ($bufferLength < 0) {
            throw new HPackException('Failed to compress headers using nghttp2');
        }

        return FFI::string($buffer, $bufferLength);
    }
}
