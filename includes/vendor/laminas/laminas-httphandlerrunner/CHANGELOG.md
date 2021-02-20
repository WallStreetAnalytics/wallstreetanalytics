# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.2.0 - 2020-06-03

### Added

- Nothing.

### Changed

- [#4](https://github.com/laminas/laminas-httphandlerrunner/pull/4) adds a call to `flush()` within the `SapiStreamEmitter`, after emitting headers and the status line, but before emitting content. This change allows providing a response to the browser more quickly, allowing it to process the stream as it is pushed.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.1.0 - 2019-02-19

### Added

- [zendframework/zend-httphandlerrunner#10](https://github.com/zendframework/zend-httphandlerrunner/pull/10) adds support for laminas-diactoros v2 releases.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.2 - 2019-02-19

### Added

- [zendframework/zend-httphandlerrunner#9](https://github.com/zendframework/zend-httphandlerrunner/pull/9) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.1 - 2018-02-21

### Added

- Nothing.

### Changed

- [zendframework/zend-httphandlerrunner#2](https://github.com/zendframework/zend-httphandlerrunner/pull/2) modifies
  how the request and error response factories are composed with the
  `RequestHandlerRunner` class. In both cases, they are now encapsulated in a
  closure which also defines a return type hint, ensuring that if the factories
  produce an invalid return type, a PHP `TypeError` will be raised.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.0 - 2018-02-05

Initial stable release.

The `Laminas\HttpRequestHandler\Emitter` subcomponent was originally released as
part of two packages:

- `EmitterInterface` and the two SAPI emitter implementations were released
  previously as part of the [laminas-diactoros](https://docs.laminas.dev/laminas-daictoros)
  package.

- `EmitterStack` was previously released as part of the
  [mezzio](https://docs.mezzio.dev/mezzio/) package.

These features are mostly verbatim from that package, with minor API changes.

The `RequestHandlerRunner` was originally developed as part of version 3
development of mezzio, but extracted here for general use with
[PSR-15](https://www.php-fig.org/psr/psr-15) applications.

### Added

- Everything.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
