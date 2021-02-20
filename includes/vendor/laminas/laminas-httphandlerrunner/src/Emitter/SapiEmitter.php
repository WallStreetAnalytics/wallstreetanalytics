<?php

/**
 * @see       https://github.com/laminas/laminas-httphandlerrunner for the canonical source repository
 * @copyright https://github.com/laminas/laminas-httphandlerrunner/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-httphandlerrunner/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\HttpHandlerRunner\Emitter;

use Psr\Http\Message\ResponseInterface;

class SapiEmitter implements EmitterInterface
{
    use SapiEmitterTrait;

    /**
     * Emits a response for a PHP SAPI environment.
     *
     * Emits the status line and headers via the header() function, and the
     * body content via the output buffer.
     */
    public function emit(ResponseInterface $response) : bool
    {
        $this->assertNoPreviousOutput();

        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        $this->emitBody($response);

        return true;
    }

    /**
     * Emit the message body.
     */
    private function emitBody(ResponseInterface $response) : void
    {
        echo $response->getBody();
    }
}
