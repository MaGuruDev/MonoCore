# MaGuru MonoCore — Monobank Integration Core for Magento 2

![Magento 2](https://img.shields.io/badge/Magento-2.4%2B-brightgreen)
[![Stable Version](https://poser.pugx.org/maguru/module-mono-core/v/stable)](https://packagist.org/packages/maguru/module-mono-core)
[![Total Downloads](https://poser.pugx.org/maguru/module-mono-core/downloads)](https://packagist.org/packages/maguru/module-mono-core)
[![License](https://poser.pugx.org/maguru/module-mono-core/license)](https://packagist.org/packages/maguru/module-mono-core)

**FREE** infrastructure module. Provides the shared HTTP clients, encrypted configuration, ECDSA webhook verification, and exception hierarchy used by all other MaGuru Monobank modules.

---

## Requirements

| Component | Version |
|-----------|---------|
| Magento Open Source / Adobe Commerce | 2.4.4+ |
| PHP | 8.1+ |
| ext-openssl | any |
| ext-json | any |
| guzzlehttp/guzzle | ^7.5 |

---

## Installation

```bash
composer require maguru/module-mono-core
bin/magento module:enable MaGuru_MonoCore
bin/magento setup:upgrade
bin/magento cache:flush
```

---

## Configuration

**Stores → Configuration → MaGuru → Monobank Integration**

| Field | Description |
|-------|-------------|
| Enabled | Enable/disable the integration |
| Acquiring API Token | Your Monobank merchant X-Token (encrypted) |
| Chast Store ID | Store ID for Monobank Частини API |
| Chast Secret | HMAC-SHA256 secret for Chast callbacks (encrypted) |
| HTTP Timeout | Guzzle request timeout in seconds (default: 15) |
| Connect Timeout | TCP connect timeout in seconds (default: 5) |
| Debug Logging | Write detailed request/response logs to `var/log/mono_core.log` |

---

## Features

- **AcquiringClient** — Guzzle HTTP client for `api.monobank.ua` with `X-Token` auth and error mapping (403→AuthException, 404→NotFoundException, 429→RateLimitException, 500→ApiException)
- **ChastClient** — Guzzle HTTP client for Monobank Частини API with HMAC-SHA256 `signature` header
- **WebhookVerifier** — ECDSA P-256 / SHA-256 verification of `X-Sign` webhook signatures with pubkey caching and auto-retry on key rotation
- **CLI** — `bin/magento mono:acquiring:validate-token` to verify your API token
- **Exception hierarchy** — `ApiException`, `AuthException`, `RateLimitException`, `NotFoundException`

---

## Developer Notes

**This module provides interfaces only — no UI, no payment method.**

Other modules interact via:

```php
// In di.xml
<preference for="MaGuru\MonoCore\Api\AcquiringClientInterface"
            type="MaGuru\MonoCore\Model\Client\AcquiringClient"/>
```

Inject `AcquiringClientInterface` or `ChastClientInterface` in your service — never instantiate clients directly.

---

## Tests

```bash
bin/clinotty php vendor/bin/phpunit -c app/code/MaGuru/MonoCore/Test/Unit/phpunit.xml
```

57 unit tests · PHPStan Level 8 ✅

---

## Support

- Email: maguru.sup@gmail.com
- Issues: via Magento Marketplace order page

---

## License

Free to use. See [LICENSE.txt](LICENSE.txt) for details.
