<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Model\Client;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use MaGuru\MonoCore\Api\AcquiringClientInterface;
use MaGuru\MonoCore\Api\ConfigInterface;
use MaGuru\MonoCore\Exception\ApiException;
use MaGuru\MonoCore\Exception\AuthException;
use MaGuru\MonoCore\Exception\NotFoundException;
use MaGuru\MonoCore\Exception\RateLimitException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AcquiringClient
 *
 * @package MaGuru\MonoCore\Model\Client
 */
class AcquiringClient implements AcquiringClientInterface
{
    private const BASE_URL  = 'https://api.monobank.ua';
    private const CMS_NAME  = 'Magento';

    private ?int $storeId = null;

    /**
     * @param ClientFactory $clientFactory
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param string $cmsVersion
     */
    public function __construct(
        private readonly ClientFactory         $clientFactory,
        private readonly ConfigInterface       $config,
        private readonly LoggerInterface       $logger,
        private readonly StoreManagerInterface $storeManager,
        private readonly string                $cmsVersion = '2.4'
    ) {}

    /**
     * @param int $storeId
     * @return static
     */
    public function withStoreId(int $storeId): static
    {
        $clone          = clone $this;
        $clone->storeId = $storeId;
        return $clone;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, [], $query);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function post(string $path, array $data = []): array
    {
        return $this->request('POST', $path, $data);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function delete(string $path, array $query = []): array
    {
        // Monobank DELETE endpoints use query params, not request body
        return $this->request('DELETE', $path, [], $query);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $body = [], array $query = []): array
    {
        $storeId = $this->storeId ?? (int) $this->storeManager->getStore()->getId();
        $token   = $this->config->getAcquiringToken($storeId);

        /** @var array<string, mixed> $options */
        $options = [
            'headers' => [
                'X-Token'       => $token,
                'X-Cms'         => self::CMS_NAME,
                'X-Cms-Version' => $this->cmsVersion,
                'Content-Type'  => 'application/json',
            ],
            'timeout'         => $this->config->getGuzzleTimeout($storeId),
            'connect_timeout' => $this->config->getConnectTimeout($storeId),
        ];

        if ($body !== []) {
            $options['json'] = $body;
        }

        if ($query !== []) {
            $options['query'] = $query;
        }

        $client = $this->clientFactory->create(['config' => ['base_uri' => self::BASE_URL]]);

        try {
            if ($this->config->isDebugLoggingEnabled($storeId)) {
                $this->logger->debug('MonoCore AcquiringClient request', [
                    'method'   => $method,
                    'path'     => $path,
                    'body'     => $body,
                    'store_id' => $storeId,
                ]);
            }

            $response = $client->request($method, $path, $options);
            $contents = $response->getBody()->getContents();
            /** @var array<string, mixed> $data */
            $data = json_decode($contents, true) ?? [];

            if ($this->config->isDebugLoggingEnabled($storeId)) {
                $this->logger->debug('MonoCore AcquiringClient response', ['path' => $path, 'data' => $data]);
            }

            return $data;
        } catch (GuzzleException $e) {
            $this->handleGuzzleException($e, $path);
        }
    }

    /**
     * @throws AuthException
     * @throws NotFoundException
     * @throws RateLimitException
     * @throws ApiException
     */
    private function handleGuzzleException(GuzzleException $e, string $path): never
    {
        $httpCode = 0;
        $errCode  = '';
        $errText  = $e->getMessage();

        if (method_exists($e, 'getResponse') && $e->getResponse() !== null) {
            $response = $e->getResponse();
            $httpCode = $response->getStatusCode();
            /** @var array<string, mixed> $body */
            $body    = json_decode((string)$response->getBody(), true) ?? [];
            $errCode = (string)($body['errCode'] ?? '');
            $errText = (string)($body['errText'] ?? $errText);
        }

        $this->logger->error('MonoCore AcquiringClient error', [
            'path'     => $path,
            'httpCode' => $httpCode,
            'errCode'  => $errCode,
            'errText'  => $errText,
        ]);

        $exception = $e instanceof \Exception ? $e : null;

        match ($httpCode) {
            403 => throw new AuthException("Monobank API auth failed: $errText", $exception),
            404 => throw new NotFoundException("Monobank API not found ($path): $errText", $exception),
            429 => throw new RateLimitException("Monobank API rate limit exceeded: $errText", $exception),
            default => throw new ApiException(
                "Monobank Acquiring API error ($httpCode): $errText",
                $httpCode,
                $errCode,
                $exception
            ),
        };
    }
}
