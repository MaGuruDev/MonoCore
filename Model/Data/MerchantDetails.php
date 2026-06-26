<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Model\Data;

use MaGuru\MonoCore\Api\Data\MerchantDetailsInterface;

/**
 * Class MerchantDetails
 *
 * @package MaGuru\MonoCore\Model\Data
 */
class MerchantDetails implements MerchantDetailsInterface
{
    /**
     * @param string $merchantId
     * @param string $merchantName
     * @param string $edrpou
     */
    public function __construct(
        private readonly string $merchantId,
        private readonly string $merchantName,
        private readonly string $edrpou
    ) {}

    /**
     * @return string
     */
    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    /**
     * @return string
     */
    public function getMerchantName(): string
    {
        return $this->merchantName;
    }

    /**
     * @return string
     */
    public function getEdrpou(): string
    {
        return $this->edrpou;
    }
}
