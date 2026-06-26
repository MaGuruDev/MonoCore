<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Controller\Adminhtml\System;

use MaGuru\MonoCore\Api\MerchantInterface;
use MaGuru\MonoCore\Exception\ApiException;
use MaGuru\MonoCore\Exception\AuthException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class ValidateToken
 *
 * @package MaGuru\MonoCore\Controller\Adminhtml\System
 */
class ValidateToken extends Action
{
    public const ADMIN_RESOURCE = 'MaGuru_MonoCore::config_mono';

    /**
     * @param Context $context
     * @param MerchantInterface $merchant
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        private readonly MerchantInterface $merchant,
        private readonly JsonFactory       $resultJsonFactory,
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();
        try {
            $details = $this->merchant->getDetails();
            return $result->setData([
                'success'     => true,
                'name'        => $details->getMerchantName(),
                'edrpou'      => $details->getEdrpou(),
                'merchant_id' => $details->getMerchantId(),
            ]);
        } catch (AuthException $e) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Authentication failed: %1', $e->getMessage()),
            ]);
        } catch (ApiException $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Unexpected error: %1', $e->getMessage()),
            ]);
        }
    }
}
