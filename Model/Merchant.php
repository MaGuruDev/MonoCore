<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Model;

use MaGuru\MonoCore\Api\AcquiringClientInterface;
use MaGuru\MonoCore\Api\Data\MerchantDetailsInterface;
use MaGuru\MonoCore\Api\MerchantInterface;
use MaGuru\MonoCore\Model\Data\MerchantDetails;

/**
 * Class Merchant
 *
 * @package MaGuru\MonoCore\Model
 */
class Merchant implements MerchantInterface
{
    /**
     * @param AcquiringClientInterface $acquiringClient
     */
    public function __construct(
        private readonly AcquiringClientInterface $acquiringClient
    ) {}

    /**
     * @param int $storeId
     * @return MerchantDetailsInterface
     */
    public function getDetails(int $storeId = 0): MerchantDetailsInterface
    {
        $data = $this->acquiringClient->get('/api/merchant/details');

        return new MerchantDetails(
            merchantId:   (string)($data['merchantId'] ?? ''),
            merchantName: (string)($data['merchantName'] ?? ''),
            edrpou:       (string)($data['edrpou'] ?? '')
        );
    }
}
