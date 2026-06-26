<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Test\Unit\Model\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MaGuru\MonoCore\Api\ConfigInterface;
use MaGuru\MonoCore\Exception\ApiException;
use MaGuru\MonoCore\Exception\AuthException;
use MaGuru\MonoCore\Exception\RateLimitException;
use MaGuru\MonoCore\Model\Client\ChastClient;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class ChastClientTest
 *
 * @package MaGuru\MonoCore\Test\Unit\Model\Client
 */
class ChastClientTest extends TestCase
{
    private ClientFactory&MockObject         $clientFactory;
    private Client&MockObject                $guzzleClient;
    private ConfigInterface&MockObject       $config;
    private LoggerInterface&MockObject       $logger;
    private StoreManagerInterface&MockObject $storeManager;
    private ChastClient                      $chastClient;

    protected function setUp(): void
    {
        $this->clientFactory = $this->createMock(ClientFactory::class);
        $this->guzzleClient  = $this->createMock(Client::class);
        $this->config        = $this->createMock(ConfigInterface::class);
        $this->logger        = $this->createMock(LoggerInterface::class);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(0);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->clientFactory->method('create')->willReturn($this->guzzleClient);
        $this->config->method('getChastBaseUrl')->willReturn('https://u2.monobank.com.ua');
        $this->config->method('getChastStoreId')->willReturn('test_store_id');
        $this->config->method('getChastSecret')->willReturn('test_secret');
        $this->config->method('getGuzzleTimeout')->willReturn(15);
        $this->config->method('getConnectTimeout')->willReturn(5);
        $this->config->method('isDebugLoggingEnabled')->willReturn(false);

        $this->chastClient = new ChastClient(
            $this->clientFactory,
            $this->config,
            $this->logger,
            $this->storeManager
        );
    }

    public function testPostReturnsDecodedArray(): void
    {
        $responseBody = (string)json_encode(['order_id' => 'order_uuid_123']);
        $response     = new Response(200, [], $responseBody);

        $this->guzzleClient->method('request')->willReturn($response);

        $result = $this->chastClient->post('/api/order/create', [
            'store_order_id' => 'M000001',
            'total_sum'      => 2499.99,
        ]);

        $this->assertSame('order_uuid_123', $result['order_id']);
    }

    public function testSignatureHeaderIsSetCorrectly(): void
    {
        $responseBody = (string)json_encode([]);
        $response     = new Response(200, [], $responseBody);

        /** @var array<string, mixed>|null $capturedOptions */
        $capturedOptions = null;
        $this->guzzleClient->method('request')
            ->willReturnCallback(
                function (string $method, string $path, array $options) use ($response, &$capturedOptions): Response {
                    $capturedOptions = $options;
                    return $response;
                }
            );

        $data = ['store_order_id' => 'M000001'];
        $this->chastClient->post('/api/order/state', $data);

        $this->assertNotNull($capturedOptions);
        $this->assertIsArray($capturedOptions['headers']);
        /** @var array<string, string> $headers */
        $headers = $capturedOptions['headers'];
        $this->assertArrayHasKey('store-id', $headers);
        $this->assertArrayHasKey('signature', $headers);

        // Verify signature matches expected HMAC-SHA256
        $body     = (string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $expected = base64_encode(hash_hmac('sha256', $body, 'test_secret', true));
        $this->assertSame($expected, $headers['signature']);
    }

    public function testThrowsAuthExceptionOn401(): void
    {
        $this->expectException(AuthException::class);

        $errorResponse = new Response(401, [], (string)json_encode(['message' => 'Unauthorized']));
        $exception     = new ClientException('401', new Request('POST', '/'), $errorResponse);

        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->chastClient->post('/api/order/create', []);
    }

    public function testThrowsRateLimitExceptionOn429(): void
    {
        $this->expectException(RateLimitException::class);

        $errorResponse = new Response(429, [], (string)json_encode(['message' => 'Too Many Requests']));
        $exception     = new ClientException('429', new Request('POST', '/'), $errorResponse);

        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->chastClient->post('/api/order/state', []);
    }

    public function testThrowsApiExceptionOnConnectionError(): void
    {
        $this->expectException(ApiException::class);

        $exception = new ConnectException('Connection refused', new Request('POST', '/'));
        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->chastClient->post('/api/order/create', ['store_order_id' => 'M001']);
    }

    public function testPostUsesBaseUrlFromConfig(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getChastBaseUrl')->willReturn('https://u2-demo-ext.mono.st4g3.com');
        $config->method('getChastStoreId')->willReturn('test_store_id');
        $config->method('getChastSecret')->willReturn('test_secret');
        $config->method('getGuzzleTimeout')->willReturn(15);
        $config->method('getConnectTimeout')->willReturn(5);
        $config->method('isDebugLoggingEnabled')->willReturn(false);

        $capturedArgs = null;
        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->method('create')
            ->willReturnCallback(function (array $args) use (&$capturedArgs): Client {
                $capturedArgs = $args;
                return $this->guzzleClient;
            });

        $response = new Response(200, [], (string)json_encode([]));
        $this->guzzleClient->method('request')->willReturn($response);

        $client = new ChastClient($clientFactory, $config, $this->logger, $this->storeManager);
        $client->post('/api/order/create', []);

        $this->assertSame('https://u2-demo-ext.mono.st4g3.com', $capturedArgs['config']['base_uri'] ?? null);
    }

    public function testPostRawReturnsBinaryString(): void
    {
        $binaryPdf = '%PDF-1.4 binary content';
        $response  = new Response(200, [], $binaryPdf);

        $this->guzzleClient->method('request')->willReturn($response);

        $result = $this->chastClient->postRaw('/api/order/guarantee/letter', [
            'order_id' => 'mono-uuid-001',
        ]);

        $this->assertSame($binaryPdf, $result);
    }

    public function testPostRawThrowsApiExceptionOnConnectionError(): void
    {
        $this->expectException(ApiException::class);

        $exception = new ConnectException('Connection refused', new Request('POST', '/'));
        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->chastClient->postRaw('/api/order/guarantee/letter', ['order_id' => 'mono-uuid-001']);
    }

    public function testPostRawThrowsAuthExceptionOn401(): void
    {
        $this->expectException(AuthException::class);

        $errorResponse = new Response(401, [], (string)json_encode(['message' => 'Unauthorized']));
        $exception     = new ClientException('401', new Request('POST', '/'), $errorResponse);

        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->chastClient->postRaw('/api/order/guarantee/letter', ['order_id' => 'mono-uuid-001']);
    }

    public function testPostRawThrowsRateLimitExceptionOn429(): void
    {
        $this->expectException(RateLimitException::class);

        $errorResponse = new Response(429, [], (string)json_encode(['message' => 'Too Many Requests']));
        $exception     = new ClientException('429', new Request('POST', '/'), $errorResponse);

        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->chastClient->postRaw('/api/order/guarantee/letter', ['order_id' => 'mono-uuid-001']);
    }

    public function testDebugLoggingCallsLoggerWhenEnabled(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getChastBaseUrl')->willReturn('https://u2.monobank.com.ua');
        $config->method('getChastStoreId')->willReturn('test_store_id');
        $config->method('getChastSecret')->willReturn('test_secret');
        $config->method('getGuzzleTimeout')->willReturn(15);
        $config->method('getConnectTimeout')->willReturn(5);
        $config->method('isDebugLoggingEnabled')->willReturn(true);

        $response = new Response(200, [], (string)json_encode(['ok' => true]));
        $this->guzzleClient->method('request')->willReturn($response);

        $this->logger->expects($this->exactly(2))->method('debug');

        $client = new ChastClient($this->clientFactory, $config, $this->logger, $this->storeManager);
        $client->post('/api/order/create', ['store_order_id' => 'M001']);
    }
}
