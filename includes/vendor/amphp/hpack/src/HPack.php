<?php

namespace Amp\Http;

use Amp\Http\Internal\HPackNative;
use Amp\Http\Internal\HPackNghttp2;

final class HPack
{
    /** @var HPackNative|HPackNghttp2 */
    private $implementation;

    public function __construct(int $tableSizeLimit = 4096)
    {
        if (HPackNghttp2::isSupported()) {
            $this->implementation = new HPackNghttp2($tableSizeLimit);
        } else {
            $this->implementation = new HPackNative($tableSizeLimit);
        }
    }

    /**
     * @param string $input Input to decode.
     * @param int    $maxSize Maximum deflated size.
     *
     * @return array|null Decoded headers.
     */
    public function decode(string $input, int $maxSize): ?array
    {
        return $this->implementation->decode($input, $maxSize);
    }

    /**
     * @param array $headers Headers to encode.
     *
     * @return string Encoded headers.
     *
     * @throws HPackException If encoding fails.
     */
    public function encode(array $headers): string
    {
        return $this->implementation->encode($headers);
    }
}
