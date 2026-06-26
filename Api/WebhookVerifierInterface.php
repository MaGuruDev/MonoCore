<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Api;

/**
 * Interface WebhookVerifierInterface
 *
 * @package MaGuru\MonoCore\Api
 */
interface WebhookVerifierInterface
{
    /**
     * Verify ECDSA signature of the webhook body.
     *
     * Fetches and caches the public key from /api/merchant/pubkey.
     * On first failure, invalidates the cache and retries with a fresh key.
     *
     * @param string $body      Raw request body bytes
     * @param string $signature Base64-encoded X-Sign header value
     */
    public function verify(string $body, string $signature): bool;
}
