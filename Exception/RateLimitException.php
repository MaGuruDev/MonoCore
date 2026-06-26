<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Exception;

/**
 * Class RateLimitException
 *
 * @package MaGuru\MonoCore\Exception
 */
class RateLimitException extends ApiException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Monobank API: rate limit exceeded (429 Too Many Requests)', ?\Exception $previous = null)
    {
        parent::__construct($message, 429, 'TMR', $previous);
    }
}
