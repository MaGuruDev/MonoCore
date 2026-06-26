<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Api;

use MaGuru\MonoCore\Exception\ApiException;
use MaGuru\MonoCore\Exception\AuthException;
use MaGuru\MonoCore\Exception\NotFoundException;
use MaGuru\MonoCore\Exception\RateLimitException;

/**
 * Interface AcquiringClientInterface
 *
 * @package MaGuru\MonoCore\Api
 */
interface AcquiringClientInterface
{
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     * @throws ApiException
     * @throws AuthException
     * @throws NotFoundException
     * @throws RateLimitException
     */
    public function get(string $path, array $query = []): array;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ApiException
     * @throws AuthException
     * @throws NotFoundException
     * @throws RateLimitException
     */
    public function post(string $path, array $data = []): array;

    /**
     * @param array<string, mixed> $query Query parameters sent as URL params (Monobank DELETE endpoints use query params)
     * @return array<string, mixed>
     * @throws ApiException
     * @throws AuthException
     * @throws NotFoundException
     * @throws RateLimitException
     */
    public function delete(string $path, array $query = []): array;

    /**
     * Returns a clone of this client bound to a specific store.
     * Use when the calling context has explicit store knowledge (e.g. order processing).
     *
     * @param int $storeId
     * @return static
     */
    public function withStoreId(int $storeId): static;
}
