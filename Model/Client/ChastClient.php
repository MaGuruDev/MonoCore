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
use MaGuru\MonoCore\Api\ChastClientInterface;
use MaGuru\MonoCore\Api\ConfigInterface;
use MaGuru\MonoCore\Exception\ApiException;
use MaGuru\MonoCore\Exception\AuthException;
use MaGuru\MonoCore\Exception\RateLimitException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ChastClient
 *
 * @package MaGuru\MonoCore\Model\Client
 */
class ChastClient implements ChastClientInterface
{
    /**
     * @param ClientFactory $clientFactory
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly ClientFactory         $clientFactory,
        private readonly ConfigInterface       $config,
        private readonly LoggerInterface       $logger,
        private readonly StoreManagerInterface $storeManager,
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function post(string $path, array $data): array
    {
        $storeId  = $this->resolveStoreId();
        $storeKey = $this->config->getChastStoreId($storeId);
        $secret   = $this->config->getChastSecret($storeId);
        $body     = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($body === false) {
            throw new ApiException('ChastClient: failed to encode request body to JSON');
        }

        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));

        /** @var array<string, mixed> $options */
        $options = [
            'headers' => [
                'store-id'     => $storeKey,
                'signature'    => $signature,
                'Content-Type' => 'application/json',
            ],
            'body'            => $body,
            'timeout'         => $this->config->getGuzzleTimeout($storeId),
            'connect_timeout' => $this->config->getConnectTimeout($storeId),
        ];

        $baseUrl = $this->config->getChastBaseUrl($storeId);
        $client  = $this->clientFactory->create(['config' => ['base_uri' => $baseUrl]]);

        try {
            if ($this->config->isDebugLoggingEnabled($storeId)) {
                $this->logger->debug('MonoCore ChastClient request', ['path' => $path, 'body' => $data]);
            }

            $response = $client->request('POST', $path, $options);
            $contents = $response->getBody()->getContents();
            /** @var array<string, mixed> $result */
            $result = json_decode($contents, true) ?? [];

            if ($this->config->isDebugLoggingEnabled($storeId)) {
                $this->logger->debug('MonoCore ChastClient response', ['path' => $path, 'data' => $result]);
            }

            return $result;
        } catch (GuzzleException $e) {
            $this->handleGuzzleException($e, $path);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @throws ApiException
     */
    public function postRaw(string $path, array $data): string
    {
        $storeId  = $this->resolveStoreId();
        $storeKey = $this->config->getChastStoreId($storeId);
        $secret   = $this->config->getChastSecret($storeId);
        $body     = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($body === false) {
            throw new ApiException('ChastClient: failed to encode request body to JSON');
        }

        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));

        /** @var array<string, mixed> $options */
        $options = [
            'headers' => [
                'store-id'     => $storeKey,
                'signature'    => $signature,
                'Content-Type' => 'application/json',
            ],
            'body'            => $body,
            'timeout'         => $this->config->getGuzzleTimeout($storeId),
            'connect_timeout' => $this->config->getConnectTimeout($storeId),
        ];

        $baseUrl = $this->config->getChastBaseUrl($storeId);
        $client  = $this->clientFactory->create(['config' => ['base_uri' => $baseUrl]]);

        try {
            $response = $client->request('POST', $path, $options);
            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            $this->handleGuzzleException($e, $path);
        }
    }

    /**
     * @return int
     */
    private function resolveStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }

    /**
     * @throws AuthException
     * @throws RateLimitException
     * @throws ApiException
     */
    private function handleGuzzleException(GuzzleException $e, string $path): never
    {
        $httpCode = 0;
        $errText  = $e->getMessage();

        if (method_exists($e, 'getResponse') && $e->getResponse() !== null) {
            $response = $e->getResponse();
            $httpCode = $response->getStatusCode();
            /** @var array<string, mixed> $body */
            $body    = json_decode((string)$response->getBody(), true) ?? [];
            $errText = (string)($body['message'] ?? $body['errText'] ?? $errText);
        }

        $this->logger->error('MonoCore ChastClient error', [
            'path'     => $path,
            'httpCode' => $httpCode,
            'errText'  => $errText,
        ]);

        $exception = $e instanceof \Exception ? $e : null;

        match ($httpCode) {
            401, 403 => throw new AuthException("Monobank Chast API auth failed: $errText", $exception),
            429      => throw new RateLimitException("Monobank Chast API rate limit: $errText", $exception),
            default  => throw new ApiException(
                "Monobank Chast API error ($httpCode): $errText",
                $httpCode,
                '',
                $exception
            ),
        };
    }
}
