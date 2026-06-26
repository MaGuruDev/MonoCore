<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Api;

/**
 * Interface ConfigInterface
 *
 * @package MaGuru\MonoCore\Api
 */
interface ConfigInterface
{
    /**
     * @param int $storeId
     * @return bool
     */
    public function isEnabled(int $storeId = 0): bool;

    /**
     * @param int $storeId
     * @return string
     */
    public function getAcquiringToken(int $storeId = 0): string;

    /**
     * @param int $storeId
     * @return string
     */
    public function getChastEnvironment(int $storeId = 0): string;

    /**
     * @param int $storeId
     * @return string
     */
    public function getChastBaseUrl(int $storeId = 0): string;

    /**
     * @param int $storeId
     * @return string
     */
    public function getChastStoreId(int $storeId = 0): string;

    /**
     * @param int $storeId
     * @return string
     */
    public function getChastSecret(int $storeId = 0): string;

    /**
     * @param int $storeId
     * @return bool
     */
    public function isDebugLoggingEnabled(int $storeId = 0): bool;

    /**
     * @param int $storeId
     * @return int
     */
    public function getGuzzleTimeout(int $storeId = 0): int;

    /**
     * @param int $storeId
     * @return int
     */
    public function getConnectTimeout(int $storeId = 0): int;
}
