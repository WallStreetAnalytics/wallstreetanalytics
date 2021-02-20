# laminas-httphandlerrunner

[![Build Status](https://travis-ci.com/laminas/laminas-httphandlerrunner.svg?branch=master)](https://travis-ci.com/laminas/laminas-httphandlerrunner)
[![Coverage Status](https://coveralls.io/repos/github/laminas/laminas-httphandlerrunner/badge.svg?branch=master)](https://coveralls.io/github/laminas/laminas-httphandlerrunner?branch=master)

This library provides utilities for:

- Emitting [PSR-7](https://www.php-fig.org/psr/psr-7) responses.
- Running [PSR-15](https://www.php-fig.org/psr/psr-15) server request handlers,
  which involves marshaling a PSR-7 `ServerRequestInterface`, handling
  exceptions due to request creation, and emitting the response returned by the
  composed request handler.

The `RequestHandlerRunner` will be used in the bootstrap of your application to
fire off the `RequestHandlerInterface` representing your application.

## Installation

Run the following to install this library:

```bash
$ composer require laminas/laminas-httphandlerrunner
```

## Documentation

Documentation is [in the doc tree](docs/book/), and can be compiled using [mkdocs](https://www.mkdocs.org):

```bash
$ mkdocs build
```

You may also [browse the documentation online](https://docs.laminas.dev/laminas-httphandlerrunner/).
