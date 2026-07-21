# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2026-07-21

### Changed

- Removed local path repository from `composer.json` for Packagist installs.

### Added

- CHANGELOG and Packagist install documentation.

## [1.1.0] - 2026-07-21

### Added

- `AiUsage` facade and manager with `for()`, `batch()`, `fake()`, and `flush()`.
- `AbstractEloquentUsageRecorder` for app-specific usage persistence.
- Tagged `vertex-ai.usage_scope_resolvers` for organization/project scoping.
- Fluent `Ai` gateway facade (optional entry point).
- Usage recording enabled by default (`VERTEX_AI_USAGE_ENABLED` opt-out).

## [1.0.2] - 2026-07-21

### Fixed

- Service provider registration order for usage recorder binding.

## [1.0.1] - 2026-07-21

### Fixed

- Testbench compatibility and config publish tags.

## [1.0.0] - 2026-07-21

### Added

- Laravel service provider, managers, batch helpers, and S3 reference image loading.

[1.1.0]: https://github.com/Kozmonos/laravel-vertex-ai/compare/v1.0.2...v1.1.0
[1.0.2]: https://github.com/Kozmonos/laravel-vertex-ai/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/Kozmonos/laravel-vertex-ai/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/Kozmonos/laravel-vertex-ai/releases/tag/v1.0.0
