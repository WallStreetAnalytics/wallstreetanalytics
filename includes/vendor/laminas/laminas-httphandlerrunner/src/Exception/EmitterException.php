<?php

/**
 * @see       https://github.com/laminas/laminas-httphandlerrunner for the canonical source repository
 * @copyright https://github.com/laminas/laminas-httphandlerrunner/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-httphandlerrunner/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\HttpHandlerRunner\Exception;

use RuntimeException;

class EmitterException extends RuntimeException implements ExceptionInterface
{
    public static function forHeadersSent() : self
    {
        return new self('Unable to emit response; headers already sent');
    }

    public static function forOutputSent() : self
    {
        return new self('Output has been emitted previously; cannot emit response');
    }
}
