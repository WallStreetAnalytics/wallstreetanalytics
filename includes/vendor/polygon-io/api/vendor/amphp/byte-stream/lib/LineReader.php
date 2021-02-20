<?php

namespace Amp\ByteStream;

use Amp\Promise;
use function Amp\call;

final class LineReader
{
    /** @var string */
    private $buffer = "";

    /** @var InputStream */
    private $source;

    public function __construct(InputStream $inputStream)
    {
        $this->source = $inputStream;
    }

    /**
     * @return Promise<string|null>
     */
    public function readLine(): Promise
    {
        return call(function () {
            if (($pos = \strpos($this->buffer, "\n")) !== false) {
                $line = \substr($this->buffer, 0, $pos);
                $this->buffer = \substr($this->buffer, $pos + 1);
                return \rtrim($line, "\r");
            }

            while (null !== $chunk = yield $this->source->read()) {
                $this->buffer .= $chunk;

                if (($pos = \strpos($this->buffer, "\n")) !== false) {
                    $line = \substr($this->buffer, 0, $pos);
                    $this->buffer = \substr($this->buffer, $pos + 1);
                    return \rtrim($line, "\r");
                }
            }

            if ($this->buffer === "") {
                return null;
            }

            $line = $this->buffer;
            $this->buffer = "";
            return \rtrim($line, "\r");
        });
    }

    public function getBuffer(): string
    {
        return $this->buffer;
    }

    public function clearBuffer()
    {
        $this->buffer = "";
    }
}
