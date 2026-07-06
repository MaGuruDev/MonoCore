# MaGuru Monobank Core — User Guide

**Version:** 1.1.6  
**Compatible with:** Adobe Commerce / Magento Open Source 2.4.4 – 2.4.x  
**PHP:** 8.1 or higher  
**Requires:** MaGuru Core  
**License:** Free

---

## Overview

MaGuru Monobank Core is the **foundation module** for all MaGuru Monobank integrations. It manages the API connection to Monobank and provides shared infrastructure for payments, installments, and fiscalization modules.

This module does not add customer-facing features on its own. It must be installed and configured before any other MaGuru Monobank module (Payment, MonoChast, Fiscal).

---

## What It Does

- Manages the **Monobank Acquiring API** connection (standard payment processing)
- Manages the **MonoChast (Частина) API** connection — a separate API for installment payments with its own authentication
- **Validates your API credentials** directly from the admin panel via an AJAX button that confirms your merchant name and EDRPOU
- Displays the correct **Webhook URL** to register in your Monobank acquiring dashboard
- Supports **per-website configuration** — each Magento website can use its own Monobank credentials
- Provides ECDSA webhook signature verification for secure webhook processing
- Supports multiple environments for MonoChast: Production, Sandbox, and Stage

---

## Requirements

| Requirement | Version / Note |
|---|---|
| Adobe Commerce / Magento Open Source | 2.4.4 or higher |
| PHP | 8.1 or higher |
| MaGuru Core | Must be installed first |
| Monobank Acquiring account | Active account with API access |

---

## Installation

### Step 1 — Install via Composer

```bash
composer require maguru/module-mono-core
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

> **Note:** If your store is in production mode, also run `bin/magento setup:static-content:deploy` after compiling.

### Step 2 — Verify Installation

```bash
bin/magento module:status MaGuru_MonoCore
```

The output should show `Module is enabled`.

---

## Configuration

Navigate to **Stores → Configuration → MaGuru → Monobank Integration**.

> **Multi-website stores:** All configuration options can be set per website. Switch the scope selector at the top of the configuration page to configure different credentials for different websites.

---

### Section 1: General

#### Enable Monobank Integration

Turns the Monobank integration on or off for the selected scope.

- **Yes** — the integration is active
- **No** — all Monobank modules are inactive; no API calls are made

Use this toggle to temporarily pause the integration without uninstalling anything.

#### HTTP Request Timeout (s)

Maximum time to wait for a response from the Monobank API.

- **Default:** 15 seconds
- Increase only if you experience timeout errors on slow connections

#### HTTP Connect Timeout (s)

Maximum time allowed to establish the initial connection to Monobank.

- **Default:** 5 seconds

#### Debug Logging

When enabled, writes detailed API request and response data to `var/log/mono_core.log`.

- **Default:** No
- Enable only for troubleshooting — disable on production stores during normal operation

---

### Section 2: Acquiring

> **This section is contributed by MaGuru Monobank Payment** and only appears once that module is installed. If you have only installed Monobank Core, you will not see these fields — that is expected, not an error.

This section configures the connection to Monobank's standard acquiring API (used by Monobank Payment).

#### API Token (X-Token)

Your Monobank acquiring API token (stored encrypted).

**Where to find it:**
1. Log in to your Monobank acquiring panel
2. Go to the **API** section
3. Copy the API token value

#### Validate Token

Click this button to verify your API token immediately. On success, the module displays your merchant name and EDRPOU number as confirmation. If you see an error, double-check the token value.

> This check connects to the Monobank API in real time and requires your server to have outbound HTTPS access to Monobank.

#### Payment Webhook URL

A read-only field displaying the URL that Monobank should call when a payment event occurs (payment completed, reversed, etc.).

**Action required:** Copy this URL and enter it in your Monobank acquiring dashboard as the webhook endpoint. Without this, your store will not receive real-time payment status updates.

#### API Base URL (Dev Override)

Optional override for the Monobank API base URL. Leave empty to use `https://api.monobank.ua`. Set this only to a local mock URL for development/testing.

---

### Section 3: MonoChast (Installments)

> **This section is contributed by MaGuru MonoChast** and only appears once that module is installed. If you have not installed MonoChast, you will not see these fields — that is expected, not an error.

This section configures the connection to Monobank's Частина (installment) API. It is only relevant if you plan to use the MaGuru MonoChast module.

#### Environment

Selects which MonoChast environment to use:

| Option | Use When |
|---|---|
| **Production** | Live store taking real installment orders |
| **Sandbox** | Testing with simulated transactions (no real money) |
| **Stage** | Monobank staging environment for pre-production testing |

#### Store ID (Production / Sandbox / Stage)

The Store ID assigned by Monobank for your Chast account. A separate field is shown for each environment: **Store ID (Production)**, **Store ID (Sandbox)**, **Store ID (Stage)**.

#### Store Secret (Production / Sandbox / Stage)

The secret key used for HMAC authentication with the MonoChast API (stored encrypted). A separate field is shown for each environment: **Store Secret (Production)**, **Store Secret (Sandbox)**, **Store Secret (Stage)**.

#### Chast Callback URL

A read-only field showing the URL Monobank should call for installment order status updates. Enter this URL in your Monobank Частина dashboard.

> Store ID, Store Secret, and Chast Callback URL are shown separately for each environment (Production / Sandbox / Stage). Ensure you fill in the correct values for the environment you are using.

---

## Frequently Asked Questions

**Q: Where do I find my Monobank API Token?**

Log in to your Monobank acquiring panel (provided by Monobank when your merchant account was activated) and navigate to the API settings section. The token is a long string that uniquely identifies your merchant account.

---

**Q: What is the Webhook URL for?**

Monobank uses this URL to send your store real-time notifications about payment events (successful payment, reversal, etc.). Without registering the webhook URL in your Monobank dashboard, your orders will not update automatically — you would need to manually check payment statuses.

---

**Q: Why are there separate credentials for Sandbox and Stage environments for MonoChast?**

Each environment is a completely separate Monobank system with its own API access, and each requires its own credentials. Use Sandbox or Stage for testing installment flows without creating real financial transactions. Switch to Production only when your store is ready to take live orders.

---

**Q: My store has multiple websites. Can each use different Monobank accounts?**

Yes. Switch the configuration scope to the desired website in the store configuration scope dropdown and enter that website's specific credentials. Each website operates independently.

---

**Q: The "Validate Token" button shows an error.**

Check the following:
1. The API Token is entered correctly (no extra spaces or characters)
2. Your server can reach the Monobank API over HTTPS (check firewall / outbound rules)
3. The API Token is active and has not expired in your Monobank panel

---

**Q: I don't see "MonoChast" settings. Is this module required?**

The MonoChast section only appears in the Monobank Integration configuration once you install the separate MaGuru MonoChast module. If you are only using Monobank Payment, you will not see this section, and that is expected — MonoChast is optional.

---

## Support

Before contacting support:

1. Run the Validate Token check in the admin configuration and note the result
2. Enable Debug Logging, reproduce the issue, and share the relevant lines from `var/log/mono_core.log`
3. Note your Magento version, PHP version, and MaGuru Monobank Core version

---

*MaGuru Monobank Core — foundation module for the MaGuru Monobank Integration Suite for Adobe Commerce*
