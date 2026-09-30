# Changelog

All notable changes to the AvraAPI Laravel SDK are documented in this file.

This project follows [Semantic Versioning](https://semver.org/) and the
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) format.

## [Unreleased]

## [1.2.0] - 2026-09-30

### Added

- Added official Laravel access to the Universal Payment Gateway through the
  `payment()` Facade and container-backed PHP SDK client.
- Added Laravel Testbench coverage for the client binding, Facade resolution,
  payment accessor, and inherited Privacy Mode option.

### Changed

- Required `avraapi/php-sdk` `^1.5.2`, including its one-request
  `withPrivacyMode()` support.
- Refreshed the public README and aligned package guidance with
  [AvraAPI Documentation](https://docs.avraapi.com).

## [1.1.2]

### Added

- Added Currency Service and Security Service functions.

## [1.0.2]

### Added

- Added the Universal Function for newly available API endpoints.

### Fixed

- Included small compatibility and bug fixes.

## [1.0.0]

### Added

- Initial public release with the base microservice integrations.
