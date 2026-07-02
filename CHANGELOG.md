# Changelog — MaGuru_MonoCore

## 1.1.6 — 2026-07-02

### Changed
- `etc/adminhtml/system.xml` — `Acquiring API` and `Chast` config groups moved out to `MonoPayment`/`MonoChast` respectively (same `Stores → Configuration → MaGuru → Monobank Integration` section, same config paths); `MonoCore` now only contributes the `General` group. Merchants who install `MonoCore` standalone (or with only one paid add-on) no longer see credential fields for modules they don't have installed.

### Fixed
- Added missing `@throws`/DocBlocks to `WebhookVerifier` private methods, `ChastClient::resolveStoreId()`, `ValidateTokenCommand::configure()` per project DocBlock conventions

## 1.0.2 — 2026-06-26

### Changed

- `composer.json`: `maguru/magento2-core` requirement bumped to `^1.0.1`
- `README.md`: added `MaGuru_Core ^1.0.1` to requirements table; PHP range updated to `8.1–8.5`

## 1.0.1 — 2026-06-26

### Added

- `.gitignore`: excludes `var/`, `generated/`, `vendor/`, `.phpunit.result.cache`, `*.log`, and IDE files

### Changed

- `LICENSE.txt`: replaced proprietary bundled-component license with "FREE TO USE" license (same as MaGuru_Core)
- `composer.json`: `license` changed to `"proprietary"`; `version` field removed — versions managed via git tags; PHP requirement extended to `~8.4.0||~8.5.0` (PHP 8.1–8.5 supported)
- `README.md`: test count corrected (28 → 57); license section updated to reference `LICENSE.txt`

## 1.0.0 — 2026-05-17

### Added
- `AcquiringClientInterface` / `AcquiringClient` — Guzzle HTTP client for Monobank Acquiring API with X-Token auth, error mapping, Magento CMS headers
- `ChastClientInterface` / `ChastClient` — Guzzle HTTP client for Monobank Частини API with HMAC-SHA256 signature header
- `WebhookVerifierInterface` / `WebhookVerifier` — ECDSA P-256 / SHA-256 verification of `X-Sign` webhook signatures; pubkey caching; auto-retry on key rotation
- `Config` — encrypted `api_token`, `chast_secret`; configurable Guzzle timeouts; debug flag
- `AcquiringClientPool` — store-view-scoped client pool for multi-store setups
- Exception hierarchy: `ApiException`, `AuthException`, `RateLimitException`, `NotFoundException`
- `MerchantInterface` / `Merchant` — `GET /api/merchant/details`; returns `MerchantDetailsInterface` (merchantId, merchantName, edrpou)
- CLI command `mono:acquiring:validate-token` — validates X-Token via `Merchant::getDetails()`; outputs merchant name/edrpou on success
- Admin section `Stores → Configuration → MaGuru → Monobank Integration`
- Virtual logger → `var/log/mono_core.log`
- 28 unit tests, PHPStan Level 8
