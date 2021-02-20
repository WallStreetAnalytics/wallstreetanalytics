# Changelog

All Notable changes to `guzzle_retry_middleware` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## v2.6.1 (2020-11-27)
### Added
- PHPStan in dev dependencies
- Additional build checks (PHPStan and PHP-CS)
- Automatic SVG badge generation for code coverage

### Fixed
- Made `GuzzleRetryMiddleware::__construct` method final
- `$options` parameter comments PHPStan was complaining about
- `shouldRetryHttpResponse` values assume that the `$response` parameter is not null
- Ensure date `$dateFormat` is never NULL or empty string in `deriveTimeoutFromHeader`
- Additional cleanup based on PHPStan report

### Removed
- Build dependency on scrutinizer.org service

## v2.6 (2020-11-24)
### Added
- GitHub Actions build status badge in `README.md`
- Support for custom date formats in `Retry-After` header via new `retry_after_date_format` option
- `max_allowable_timeout_secs` option to set a ceiling on the maximum time the client is willing to wait between requests
- Support for Guzzle 7 class-based static methods

### Changed
- Removed unnecessary comments
- Name of Github Action to `Github Build`

### Removed
- `.travis.yml` build support (switched to Github Actions)

## v2.5 (2020-11-02)
### Added
- Ability to handle non-integer values in `Retry-After` headers (thanks @andrewdalpino)
- Beginning GitHub Workflows code (support for Travis-CI will be removed in the next minor version)
- Support for PHP v8.0 in `composer.json`

## v2.4 (2020-08-19)
### Added
- Option to specify custom HTTP header name other than `Retry-After` (thanks @jamesaspence)

### Changed
- Added a few things to `.gitignore` (minor)
- Updated `phpunit.xml.dist` to latest spec

### Removed
- Removed build tests for PHP 7.1 in `.travis.yml`

## v2.3.3 (2020-05-17)

### Changed
- Minimum allowed version of PHPUnit is v7.5
- Made version constraint syntax consistent in `composer.json`
- Updated alias for `dev-master` to `2.0-dev` in `composer.json`

### Fixed
- Cleaned up comments and updated syntax in tests to be compatible with newer versions of PHPUnit (v8 and v9)

## v2.3.2 (2020-01-27)

### Added
- PHP 7.4 build test in `.travis.yml` (thanks @alexeyshockov)
- Guzzle v7 support in `composer.json` (thanks @alexeyshockov)

## v2.3.1 (2019-10-28)

### Added
- `declare(strict_types=1)` in unit test file

### Changed
- Fixes to README.md
- Code tweaks: Upgrade to PSR-12 compliance

## v2.3 (2019-09-16)

### Added
- PHP 7 goodness: `declare(strict_types=1)` and method return signatures
- PHP v7.3 tests in `.travis.yml`

### Changed
- Made minimum requirement for PHP v7.1 (note: this is considered a [compatible change](https://semver.org/#what-should-i-do-if-i-update-my-own-dependencies-without-changing-the-public-api))
- Updated to Carbon 2.0 (only affects tests)
- The `$request` and `$options` variables are now passed by reference in the retry callback to allow for modification (thanks @Krunch!)  

### Removed
- Removed unsupported tests for unsupported PHP versions from `.travis.yml` file
- Removed support for older versions of PHPUnit 

### Fixed
- Always ensure positive integer used when calculating delay timeout (fixes #12)
- Retry connect exception regardless of cURL error code (thanks @LeoniePhiline) (fixes #14)

## v2.2 (2018-06-03)

### Added
- Added `expose_retry_header` and `retry_header` options for debugging purposes (thanks, @coudenysj)
- Travis CI now tests PHP v7.2

### Changed
- Allow newer versions of PHPUnit in `composer.json` (match Guzzle composer.json PHPUnit requirements)

### Fixed
- Refactored data provider method name in PHPUnit test (`testRetryOccursWhenStatusCodeMatchesProvider` 
  → `providerForRetryOccursWhenStatusCodeMatches`)
- Use PHPUnit new namespaced class name
- Fix `phpunit.xml.dist` specification so that PHPUnit no longer emits warnings
- Travis CI should use lowest library versions on lowest supported version of PHP (v5.5, not 5.6)  

### Removed
- `hhvm` tests in Travis CI; they were causing builds to fail

## v2.1 (2018-02-13)

### Added
- Added `retry_enabled` parameter to allow quick disable of retry on specific requests
- Added ability to pass in a callable to `default_retry_multiplier` in order to implement custom delay logic

## v2.0 (2017-10-02)

### Added
- Added ability to retry on connect or request timeout (`retry_on_timeout` option)
- Added better tests for retry callback

### Changed
- Changed callback signature for `on_retry_callback` callback.  Response object is no longer guaranteed to be present,
  so the callback signature now looks like this: 
  `(int $retryCount, int $delayTimeout, RequestInterface $request, array $options, ResponseInterface|null $response)`.
- Updated Guzzle requirement to v6.3 or newer

### Fixed
- Clarified and cleaned up some documentation in README, including a typo.

## v1.0 (2017-07-29)

### Added
- Everything; this is the initial version.
