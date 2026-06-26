<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Api;

use MaGuru\MonoCore\Api\Data\MerchantDetailsInterface;
use MaGuru\MonoCore\Exception\ApiException;
use MaGuru\MonoCore\Exception\AuthException;

/**
 * Interface MerchantInterface
 *
 * @package MaGuru\MonoCore\Api
 */
interface MerchantInterface
{
    /**
     * Fetch merchant details from GET /api/merchant/details.
     * Used for token validation and admin display.
     *
     * @throws ApiException
     * @throws AuthException
     */
    public function getDetails(int $storeId = 0): MerchantDetailsInterface;
}
