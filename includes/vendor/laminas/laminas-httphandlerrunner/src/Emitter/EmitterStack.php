<?php

/**
 * @see       https://github.com/laminas/laminas-httphandlerrunner for the canonical source repository
 * @copyright https://github.com/laminas/laminas-httphandlerrunner/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-httphandlerrunner/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\HttpHandlerRunner\Emitter;

use Laminas\HttpHandlerRunner\Exception;
use Psr\Http\Message\ResponseInterface;
use SplStack;

/**
 * Provides an EmitterInterface implementation that acts as a stack of Emitters.
 *
 * The implementations emit() method iterates itself.
 *
 * When iterating the stack, the first emitter to return a boolean
 * true value will short-circuit iteration.
 */
class EmitterStack extends SplStack implements EmitterInterface
{
    /**
     * Emit a response
     *
     * Loops through the stack, calling emit() on each; any that return a
     * boolean true value will short-circuit, skipping any remaining emitters
     * in the stack.
     *
     * As such, return a boolean false value from an emitter to indicate it
     * cannot emit the response, allowing the next emitter to try.
     */
    public function emit(ResponseInterface $response) : bool
    {
        foreach ($this as $emitter) {
            if (false !== $emitter->emit($response)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set an emitter on the stack by index.
     *
     * @param mixed $index
     * @param EmitterInterface $emitter
     * @return void
     * @throws InvalidArgumentException if not an EmitterInterface instance
     */
    public function offsetSet($index, $emitter)
    {
        $this->validateEmitter($emitter);
        parent::offsetSet($index, $emitter);
    }

    /**
     * Push an emitter to the stack.
     *
     * @param EmitterInterface $emitter
     * @return void
     * @throws InvalidArgumentException if not an EmitterInterface instance
     */
    public function push($emitter)
    {
        $this->validateEmitter($emitter);
        parent::push($emitter);
    }

    /**
     * Unshift an emitter to the stack.
     *
     * @param EmitterInterface $emitter
     * @return void
     * @throws InvalidArgumentException if not an EmitterInterface instance
     */
    public function unshift($emitter)
    {
        $this->validateEmitter($emitter);
        parent::unshift($emitter);
    }

    /**
     * Validate that an emitter implements EmitterInterface.
     *
     * @param mixed $emitter
     * @throws Exception\InvalidEmitterException for non-emitter instances
     */
    private function validateEmitter($emitter) : void
    {
        if (! $emitter instanceof EmitterInterface) {
            throw Exception\InvalidEmitterException::forEmitter($emitter);
        }
    }
}
