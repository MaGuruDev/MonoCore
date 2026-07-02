<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Model;

use MaGuru\MonoCore\Api\AcquiringClientInterface;
use MaGuru\MonoCore\Api\WebhookVerifierInterface;
use MaGuru\MonoCore\Exception\ApiException;
use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

/**
 * Class WebhookVerifier
 *
 * @package MaGuru\MonoCore\Model
 */
class WebhookVerifier implements WebhookVerifierInterface
{
    private const CACHE_KEY      = 'mono_acquiring_pubkey';
    private const CACHE_LIFETIME = 3600; // 1 hour

    /**
     * @param AcquiringClientInterface $acquiringClient
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly AcquiringClientInterface $acquiringClient,
        private readonly CacheInterface           $cache,
        private readonly LoggerInterface          $logger
    ) {}

    /**
     * @param string $body
     * @param string $signature
     * @return bool
     */
    public function verify(string $body, string $signature): bool
    {
        $pubkeyPem = $this->getPublicKeyPem();

        if ($pubkeyPem === '') {
            $this->logger->error('MonoCore WebhookVerifier: public key is empty, cannot verify signature');
            return false;
        }

        if ($this->verifySignature($pubkeyPem, $body, $signature)) {
            return true;
        }

        // Key may have been rotated — invalidate cache and retry once
        $this->cache->remove(self::CACHE_KEY);
        $freshPem = $this->fetchAndCachePubkey();

        if ($freshPem === '' || $freshPem === $pubkeyPem) {
            return false;
        }

        return $this->verifySignature($freshPem, $body, $signature);
    }

    /**
     * @return string
     */
    private function getPublicKeyPem(): string
    {
        $cached = $this->cache->load(self::CACHE_KEY);
        if ($cached !== false && $cached !== '') {
            return $cached;
        }

        return $this->fetchAndCachePubkey();
    }

    /**
     * @return string
     */
    private function fetchAndCachePubkey(): string
    {
        try {
            $response = $this->acquiringClient->get('/api/merchant/pubkey');
            $key      = (string)($response['key'] ?? '');

            if ($key === '') {
                $this->logger->error('MonoCore WebhookVerifier: empty key in pubkey response');
                return '';
            }

            // Response 'key' is base64-encoded PEM
            $pem = base64_decode($key, strict: true);
            if ($pem === false) {
                $this->logger->error('MonoCore WebhookVerifier: failed to base64-decode public key');
                return '';
            }

            $this->cache->save($pem, self::CACHE_KEY, [], self::CACHE_LIFETIME);
            return $pem;
        } catch (ApiException $e) {
            $this->logger->error('MonoCore WebhookVerifier: failed to fetch public key', [
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * @param string $pubkeyPem
     * @param string $body
     * @param string $signatureBase64
     * @return bool
     */
    private function verifySignature(string $pubkeyPem, string $body, string $signatureBase64): bool
    {
        $publicKey = openssl_pkey_get_public($pubkeyPem);
        if ($publicKey === false) {
            $this->logger->error('MonoCore WebhookVerifier: failed to parse public key PEM', [
                'openssl_error' => openssl_error_string(),
            ]);
            return false;
        }

        $signature = base64_decode($signatureBase64, strict: true);
        if ($signature === false) {
            $this->logger->error('MonoCore WebhookVerifier: failed to base64-decode X-Sign header');
            return false;
        }

        $result = openssl_verify($body, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($result === -1) {
            $this->logger->error('MonoCore WebhookVerifier: openssl_verify error', [
                'openssl_error' => openssl_error_string(),
            ]);
            return false;
        }

        return $result === 1;
    }
}
