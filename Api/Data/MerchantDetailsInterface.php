<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Api\Data;

/**
 * Interface MerchantDetailsInterface
 *
 * @package MaGuru\MonoCore\Api\Data
 */
interface MerchantDetailsInterface
{
    /**
     * @return string
     */
    public function getMerchantId(): string;

    /**
     * @return string
     */
    public function getMerchantName(): string;

    /**
     * @return string
     */
    public function getEdrpou(): string;
}
