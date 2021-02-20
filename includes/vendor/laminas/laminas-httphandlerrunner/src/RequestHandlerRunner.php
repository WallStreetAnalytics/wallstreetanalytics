<?php

/**
 * @see       https://github.com/laminas/laminas-httphandlerrunner for the canonical source repository
 * @copyright https://github.com/laminas/laminas-httphandlerrunner/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-httphandlerrunner/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\HttpHandlerRunner;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * "Run" a request handler.
 *
 * The RequestHandlerRunner will marshal a request using the composed factory, and
 * then pass the request to the composed handler. Finally, it emits the response
 * returned by the handler using the composed emitter.
 *
 * If the factory for generating the request raises an exception or throwable,
 * then the runner will use the composed error response generator to generate a
 * response, based on the exception or throwable raised.
 */
class RequestHandlerRunner
{
    /**
     * @var Emitter\EmitterInterface
     */
    private $emitter;

    /**
     * A request handler to run as the application.
     *
     * @var RequestHandlerInterface
     */
    private $handler;

    /**
     * A factory capable of generating an error response in the scenario that
     * the $serverRequestFactory raises an exception during generation of the
     * request instance.
     *
     * The factory will receive the Throwable or Exception that caused the error,
     * and must return a Psr\Http\Message\ResponseInterface instance.
     *
     * @var callable
     */
    private $serverRequestErrorResponseGenerator;

    /**
     * A factory capable of generating a Psr\Http\Message\ServerRequestInterface instance.
     * The factory will not receive any arguments.
     *
     * @var callable
     */
    private $serverRequestFactory;

    public function __construct(
        RequestHandlerInterface $handler,
        Emitter\EmitterInterface $emitter,
        callable $serverRequestFactory,
        callable $serverRequestErrorResponseGenerator
    ) {
        $this->handler = $handler;
        $this->emitter = $emitter;

        // Factories are cast as Closures to ensure return type safety.
        $this->serverRequestFactory = function () use ($serverRequestFactory) : ServerRequestInterface {
            return $serverRequestFactory();
        };

        $this->serverRequestErrorResponseGenerator =
            function (Throwable $exception) use ($serverRequestErrorResponseGenerator) : ResponseInterface {
                return $serverRequestErrorResponseGenerator($exception);
            };
    }

    /**
     * Run the application
     */
    public function run() : void
    {
        try {
            $request = ($this->serverRequestFactory)();
        } catch (Throwable $e) {
            // Error in generating the request
            $this->emitMarshalServerRequestException($e);
            return;
        }

        $response = $this->handler->handle($request);

        $this->emitter->emit($response);
    }

    private function emitMarshalServerRequestException(Throwable $exception) : void
    {
        $response = ($this->serverRequestErrorResponseGenerator)($exception);
        $this->emitter->emit($response);
    }
}
