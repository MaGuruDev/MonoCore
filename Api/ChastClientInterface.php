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
use MaGuru\MonoCore\Exception\RateLimitException;

/**
 * Interface ChastClientInterface
 *
 * @package MaGuru\MonoCore\Api
 */
interface ChastClientInterface
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ApiException
     * @throws AuthException
     * @throws RateLimitException
     */
    public function post(string $path, array $data): array;

    /**
     * POST request returning raw response body bytes (e.g. binary PDF).
     *
     * @param array<string, mixed> $data
     * @throws ApiException
     * @throws AuthException
     * @throws RateLimitException
     */
    public function postRaw(string $path, array $data): string;
}
