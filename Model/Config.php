<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Model;

use MaGuru\Core\Model\AbstractConfig;
use MaGuru\MonoCore\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Class Config
 *
 * @package MaGuru\MonoCore\Model
 */
class Config extends AbstractConfig implements ConfigInterface
{
    private const XML_PATH_ENABLED              = 'mono/core/enabled';
    private const XML_PATH_DEBUG_LOGGING        = 'mono/core/debug_logging';
    private const XML_PATH_GUZZLE_TIMEOUT       = 'mono/core/guzzle_timeout';
    private const XML_PATH_CONNECT_TIMEOUT      = 'mono/core/connect_timeout';
    private const XML_PATH_ACQUIRING_TOKEN      = 'mono/acquiring/api_token';
    private const XML_PATH_ACQUIRING_BASE_URL   = 'mono/acquiring/api_base_url';
    private const XML_PATH_CHAST_ENVIRONMENT    = 'mono/chast/environment';
    private const XML_PATH_CHAST_STORE_ID       = 'mono/chast/store_id';
    private const XML_PATH_CHAST_SECRET         = 'mono/chast/secret';
    private const XML_PATH_CHAST_STORE_ID_SANDBOX = 'mono/chast/store_id_sandbox';
    private const XML_PATH_CHAST_SECRET_SANDBOX = 'mono/chast/secret_sandbox';
    private const XML_PATH_CHAST_STORE_ID_STAGE = 'mono/chast/store_id_stage';
    private const XML_PATH_CHAST_SECRET_STAGE   = 'mono/chast/secret_stage';

    private const CHAST_BASE_URLS = [
        'sandbox'    => 'https://u2-demo-ext.mono.st4g3.com',
        'stage'      => 'https://u2-ext.mono.st4g3.com',
        'production' => 'https://u2.monobank.com.ua',
    ];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
        parent::__construct($scopeConfig);
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isEnabled(int $storeId = 0): bool
    {
        return $this->isFlag(self::XML_PATH_ENABLED, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getAcquiringToken(int $storeId = 0): string
    {
        $encrypted = $this->getValue(self::XML_PATH_ACQUIRING_TOKEN, $storeId);
        return $encrypted !== '' ? $this->encryptor->decrypt($encrypted) : '';
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getChastEnvironment(int $storeId = 0): string
    {
        $env = $this->getValue(self::XML_PATH_CHAST_ENVIRONMENT, $storeId);
        return in_array($env, ['sandbox', 'stage', 'production'], true) ? $env : 'production';
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getChastBaseUrl(int $storeId = 0): string
    {
        return self::CHAST_BASE_URLS[$this->getChastEnvironment($storeId)];
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getChastStoreId(int $storeId = 0): string
    {
        $path = match ($this->getChastEnvironment($storeId)) {
            'sandbox' => self::XML_PATH_CHAST_STORE_ID_SANDBOX,
            'stage'   => self::XML_PATH_CHAST_STORE_ID_STAGE,
            default   => self::XML_PATH_CHAST_STORE_ID,
        };
        return $this->getValue($path, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getChastSecret(int $storeId = 0): string
    {
        $path = match ($this->getChastEnvironment($storeId)) {
            'sandbox' => self::XML_PATH_CHAST_SECRET_SANDBOX,
            'stage'   => self::XML_PATH_CHAST_SECRET_STAGE,
            default   => self::XML_PATH_CHAST_SECRET,
        };
        $encrypted = $this->getValue($path, $storeId);
        return $encrypted !== '' ? $this->encryptor->decrypt($encrypted) : '';
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getAcquiringBaseUrl(int $storeId = 0): string
    {
        return $this->getValue(self::XML_PATH_ACQUIRING_BASE_URL, $storeId);
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isDebugLoggingEnabled(int $storeId = 0): bool
    {
        return $this->isFlag(self::XML_PATH_DEBUG_LOGGING, $storeId);
    }

    /**
     * @param int $storeId
     * @return int
     */
    public function getGuzzleTimeout(int $storeId = 0): int
    {
        $value = $this->getIntValue(self::XML_PATH_GUZZLE_TIMEOUT, $storeId);
        return $value > 0 ? $value : 15;
    }

    /**
     * @param int $storeId
     * @return int
     */
    public function getConnectTimeout(int $storeId = 0): int
    {
        $value = $this->getIntValue(self::XML_PATH_CONNECT_TIMEOUT, $storeId);
        return $value > 0 ? $value : 5;
    }
}
