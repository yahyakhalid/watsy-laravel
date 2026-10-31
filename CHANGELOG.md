# Changelog

All notable changes to `watsy/laravel` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- GitHub Actions workflow that runs the test suite on PHP 8.1 to 8.4.
- Dependabot updates for Composer and GitHub Actions.
- Security policy, bug report issue form and `.editorconfig`.
- Version, tests and license badges in the README, and support links in `composer.json`.

### Changed
- Tests and CI files are left out of the dist archive.

## [0.1.1] - 2026-10-29

### Changed
- Republished the 0.1.0 code under a new tag. No code changes.

## 0.1.0 - 2026-10-29

The `v0.1.0` tag has been withdrawn. Use 0.1.1, which ships the same code.

### Added
- `WatsyClient` for the Watsy v1 API: `verify`, `check`, `verification`, `cancel`, `messages` and `balance`.
- Laravel service provider, `Watsy` facade and publishable `config/watsy.php`.
- `VerifyWatsySignature` middleware and `Webhook::constructEvent()` for signed webhooks.
- `WatsyException` with a stable error code and English and Arabic messages.
- Idempotency keys for safe retries.

[Unreleased]: https://github.com/yahyakhalid/watsy-laravel/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/yahyakhalid/watsy-laravel/tree/v0.1.1
