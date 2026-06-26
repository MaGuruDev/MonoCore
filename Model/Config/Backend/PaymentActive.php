<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Class PaymentActive
 *
 * @package MaGuru\MonoCore\Model\Config\Backend
 */
class PaymentActive extends Value
{
    private const REQUIRED_CURRENCY = 'UAH';
    private const XML_PATH_ALLOWED_CURRENCIES = 'currency/options/allow';

    /**
     * @param array<mixed> $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        private readonly ManagerInterface $messageManager,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return self
     */
    public function beforeSave(): self
    {
        if ($this->getValue() !== '1') {
            return parent::beforeSave();
        }

        $scope = $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeCode = (string) ($this->getData('scope_code') ?? '');

        $allowedRaw = (string) $this->_config->getValue(
            self::XML_PATH_ALLOWED_CURRENCIES,
            $scope,
            $scopeCode
        );
        $allowedCodes = array_filter(array_map('trim', explode(',', $allowedRaw)));

        if (!in_array(self::REQUIRED_CURRENCY, $allowedCodes, true)) {
            $this->setValue('0');
            $this->messageManager->addWarningMessage(
                (string) __('Monobank payment method was automatically disabled: UAH currency is not in Allowed Currencies for this scope. Enable UAH in Stores → Currency → Allowed Currencies first.')
            );
        }

        return parent::beforeSave();
    }
}
