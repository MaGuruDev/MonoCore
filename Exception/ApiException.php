<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Exception;

use MaGuru\Core\Exception\ApiException as CoreApiException;

/**
 * Class ApiException
 *
 * @package MaGuru\MonoCore\Exception
 */
class ApiException extends CoreApiException
{
    /**
     * @param string $message
     * @param int $httpCode
     * @param string $errCode
     * @param \Exception|null $previous
     */
    public function __construct(
        string $message,
        private readonly int $httpCode = 0,
        private readonly string $errCode = '',
        ?\Exception $previous = null
    ) {
        parent::__construct(new \Magento\Framework\Phrase($message), $previous);
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @return string
     */
    public function getErrCode(): string
    {
        return $this->errCode;
    }
}
