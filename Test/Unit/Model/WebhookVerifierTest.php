<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Test\Unit\Model;

use MaGuru\MonoCore\Api\AcquiringClientInterface;
use MaGuru\MonoCore\Exception\ApiException;
use MaGuru\MonoCore\Model\WebhookVerifier;
use Magento\Framework\App\CacheInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class WebhookVerifierTest
 *
 * @package MaGuru\MonoCore\Test\Unit\Model
 */
class WebhookVerifierTest extends TestCase
{
    private AcquiringClientInterface&MockObject $acquiringClient;
    private CacheInterface&MockObject           $cache;
    private LoggerInterface&MockObject          $logger;
    private WebhookVerifier                     $verifier;

    protected function setUp(): void
    {
        $this->acquiringClient = $this->createMock(AcquiringClientInterface::class);
        $this->cache           = $this->createMock(CacheInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);

        $this->verifier = new WebhookVerifier(
            $this->acquiringClient,
            $this->cache,
            $this->logger
        );
    }

    public function testVerifyReturnsFalseWhenCacheEmptyAndApiThrows(): void
    {
        $this->cache->method('load')->willReturn(false);
        $this->acquiringClient->method('get')->willThrowException(new ApiException('API error'));
        $this->logger->expects($this->atLeastOnce())->method('error');

        $result = $this->verifier->verify('body', 'invalidsig');

        $this->assertFalse($result);
    }

    public function testVerifyReturnsFalseWhenPubkeyIsEmpty(): void
    {
        $this->cache->method('load')->willReturn(false);
        $this->acquiringClient->method('get')->willReturn(['key' => '']);
        $this->logger->expects($this->atLeastOnce())->method('error');

        $result = $this->verifier->verify('body', 'sig');

        $this->assertFalse($result);
    }

    public function testVerifyReturnsFalseOnInvalidBase64Signature(): void
    {
        $this->cache->method('load')->willReturn('not-real-pem-content');

        $result = $this->verifier->verify('body', 'not!valid!base64!!!');

        $this->assertFalse($result);
    }

    public function testVerifyFetchesNewKeyOnVerificationFailureWithCachedKey(): void
    {
        // Cache has a key but it's not a valid PEM → verification fails → API is called for retry
        $this->cache->method('load')->willReturn('invalid-pem');
        $this->acquiringClient->expects($this->once())
            ->method('get')
            ->willReturn(['key' => base64_encode('also-invalid-pem')]);

        $result = $this->verifier->verify('body', base64_encode('invalidsig'));

        $this->assertFalse($result);
    }

    public function testVerifyInvalidatesCacheAndCallsApiOnRetry(): void
    {
        // cache->load is only called ONCE (in getPublicKeyPem).
        // After verification failure, fetchAndCachePubkey goes directly to API — no second cache->load.
        $this->cache->expects($this->once())
            ->method('load')
            ->willReturn('invalid-pem');

        $this->cache->expects($this->once())->method('remove');
        $this->acquiringClient->expects($this->once())
            ->method('get')
            ->willReturn(['key' => base64_encode('new-pem')]);

        $result = $this->verifier->verify('body', base64_encode('invalidsig'));

        $this->assertFalse($result);
    }

    public function testVerifyReturnsFalseWhenPubkeyBase64DecodeFails(): void
    {
        // API returns a 'key' field that contains non-base64 characters (strict mode rejects it)
        $this->cache->method('load')->willReturn(false);
        $this->acquiringClient->method('get')->willReturn(['key' => 'not!valid@base64#key$content']);
        $this->logger->expects($this->atLeastOnce())->method('error');

        $result = $this->verifier->verify('body', 'sig');

        $this->assertFalse($result);
    }

    public function testVerifyReturnsTrueForValidSignature(): void
    {
        $keyPair = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $this->assertNotFalse($keyPair);
        openssl_pkey_export($keyPair, $privateKeyPem);
        $details = openssl_pkey_get_details($keyPair);
        $this->assertNotFalse($details);
        $pubkeyPem = (string) $details['key'];

        $body = '{"order_id":"test-order-happy-path"}';
        openssl_sign($body, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);
        $signatureBase64 = base64_encode($signature);

        $this->cache->method('load')->willReturn($pubkeyPem);

        $this->assertTrue($this->verifier->verify($body, $signatureBase64));
    }

    public function testVerifyReturnsFalseWhenFreshKeyMatchesCachedKey(): void
    {
        // When cache and fresh key are identical, the retry branch is skipped → false
        $this->cache->method('load')->willReturn('some-pem-content');
        $this->acquiringClient->method('get')
            ->willReturn(['key' => base64_encode('some-pem-content')]);

        $result = $this->verifier->verify('body', base64_encode('some-signature'));

        $this->assertFalse($result);
    }
}
