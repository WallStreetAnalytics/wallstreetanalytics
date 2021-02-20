<?php

/**
 * @see       https://github.com/laminas/laminas-httphandlerrunner for the canonical source repository
 * @copyright https://github.com/laminas/laminas-httphandlerrunner/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-httphandlerrunner/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\HttpHandlerRunner\Exception;

use InvalidArgumentException;
use Laminas\HttpHandlerRunner\Emitter;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

class InvalidEmitterException extends InvalidArgumentException implements ExceptionInterface
{
    /**
     * @var mixed $emitter Invalid emitter type
     */
    public static function forEmitter($emitter) : self
    {
        return new self(sprintf(
            '%s can only compose %s implementations; received %s',
            Emitter\EmitterStack::class,
            Emitter\EmitterInterface::class,
            is_object($emitter) ? get_class($emitter) : gettype($emitter)
        ));
    }
}
