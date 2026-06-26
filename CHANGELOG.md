# Changelog — MaGuru_MonoCore

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
