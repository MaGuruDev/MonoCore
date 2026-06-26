<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Exception;

/**
 * Class AuthException
 *
 * @package MaGuru\MonoCore\Exception
 */
class AuthException extends ApiException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Monobank API: authentication failed (403 Forbidden)', ?\Exception $previous = null)
    {
        parent::__construct($message, 403, 'FORBIDDEN', $previous);
    }
}
