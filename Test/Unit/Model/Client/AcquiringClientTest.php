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
use MaGuru\MonoCore\Exception\AuthException;
use MaGuru\MonoCore\Exception\NotFoundException;
use MaGuru\MonoCore\Exception\RateLimitException;
use MaGuru\MonoCore\Exception\ApiException;
use MaGuru\MonoCore\Model\Client\AcquiringClient;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class AcquiringClientTest
 *
 * @package MaGuru\MonoCore\Test\Unit\Model\Client
 */
class AcquiringClientTest extends TestCase
{
    private ClientFactory&MockObject          $clientFactory;
    private Client&MockObject                 $guzzleClient;
    private ConfigInterface&MockObject        $config;
    private LoggerInterface&MockObject        $logger;
    private StoreManagerInterface&MockObject  $storeManager;
    private AcquiringClient                   $acquiringClient;

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
        $this->config->method('getAcquiringToken')->willReturn('test_token');
        $this->config->method('getGuzzleTimeout')->willReturn(15);
        $this->config->method('getConnectTimeout')->willReturn(5);
        $this->config->method('isDebugLoggingEnabled')->willReturn(false);

        $this->acquiringClient = new AcquiringClient(
            $this->clientFactory,
            $this->config,
            $this->logger,
            $this->storeManager
        );
    }

    public function testGetReturnsDecodedArray(): void
    {
        $responseBody = (string)json_encode(['merchantId' => 'abc123', 'merchantName' => 'Test']);
        $response     = new Response(200, [], $responseBody);

        $this->guzzleClient->method('request')->willReturn($response);

        $result = $this->acquiringClient->get('/api/merchant/details');

        $this->assertSame('abc123', $result['merchantId']);
        $this->assertSame('Test', $result['merchantName']);
    }

    public function testPostReturnsDecodedArray(): void
    {
        $responseBody = (string)json_encode(['invoiceId' => 'inv_123', 'pageUrl' => 'https://pay.monobank.ua/pay/inv_123']);
        $response     = new Response(200, [], $responseBody);

        $this->guzzleClient->method('request')->willReturn($response);

        $result = $this->acquiringClient->post('/api/merchant/invoice/create', ['amount' => 4200]);

        $this->assertSame('inv_123', $result['invoiceId']);
    }

    public function testThrowsAuthExceptionOn403(): void
    {
        $this->expectException(AuthException::class);

        $errorResponse = new Response(403, [], (string)json_encode(['errCode' => 'FORBIDDEN', 'errText' => 'forbidden']));
        $exception     = new ClientException('403', new Request('GET', '/'), $errorResponse);

        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->acquiringClient->get('/api/merchant/details');
    }

    public function testThrowsNotFoundExceptionOn404(): void
    {
        $this->expectException(NotFoundException::class);

        $errorResponse = new Response(404, [], (string)json_encode(['errCode' => 'NOT_FOUND', 'errText' => 'not found']));
        $exception     = new ClientException('404', new Request('GET', '/'), $errorResponse);

        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->acquiringClient->get('/api/merchant/invoice/status');
    }

    public function testThrowsRateLimitExceptionOn429(): void
    {
        $this->expectException(RateLimitException::class);

        $errorResponse = new Response(429, [], (string)json_encode(['errCode' => 'TMR', 'errText' => 'too many requests']));
        $exception     = new ClientException('429', new Request('POST', '/'), $errorResponse);

        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->acquiringClient->post('/api/merchant/invoice/create', ['amount' => 100]);
    }

    public function testThrowsApiExceptionOn500(): void
    {
        $this->expectException(ApiException::class);

        $errorResponse = new Response(500, [], (string)json_encode(['errCode' => 'INTERNAL_ERROR', 'errText' => 'internal server error']));
        $exception     = new ClientException('500', new Request('POST', '/'), $errorResponse);

        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->acquiringClient->post('/api/merchant/invoice/create', []);
    }

    public function testThrowsApiExceptionOn401(): void
    {
        $this->expectException(ApiException::class);

        $errorResponse = new Response(401, [], (string)json_encode(['errCode' => 'UNAUTHORIZED', 'errText' => 'unauthorized']));
        $exception     = new ClientException('401', new Request('GET', '/'), $errorResponse);

        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->acquiringClient->get('/api/merchant/details');
    }

    public function testDeleteSendsDeleteHttpMethod(): void
    {
        $capturedMethod = '';
        $response = new Response(200, [], '{}');

        $this->guzzleClient->method('request')
            ->willReturnCallback(function (string $m, string $p, array $opts) use (&$capturedMethod, $response): Response {
                $capturedMethod = $m;
                return $response;
            });

        $this->acquiringClient->delete('/api/merchant/invoice/remove', ['invoiceId' => 'inv-del-1']);

        $this->assertSame('DELETE', $capturedMethod);
    }

    public function testThrowsApiExceptionOnConnectionError(): void
    {
        $this->expectException(ApiException::class);

        $exception = new ConnectException('Connection refused', new Request('GET', '/'));
        $this->guzzleClient->method('request')->willThrowException($exception);

        $this->acquiringClient->get('/api/merchant/details');
    }

    public function testDebugLoggingCallsLoggerWhenEnabled(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getAcquiringToken')->willReturn('tok');
        $config->method('getGuzzleTimeout')->willReturn(15);
        $config->method('getConnectTimeout')->willReturn(5);
        $config->method('isDebugLoggingEnabled')->willReturn(true);

        $response = new Response(200, [], (string)json_encode(['ok' => true]));
        $this->guzzleClient->method('request')->willReturn($response);

        $this->logger->expects($this->exactly(2))->method('debug');

        $client = new AcquiringClient($this->clientFactory, $config, $this->logger, $this->storeManager);
        $client->get('/api/merchant/details');
    }
}
