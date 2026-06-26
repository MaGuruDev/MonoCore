<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Exception;

/**
 * Class NotFoundException
 *
 * @package MaGuru\MonoCore\Exception
 */
class NotFoundException extends ApiException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Monobank API: resource not found (404 Not Found)', ?\Exception $previous = null)
    {
        parent::__construct($message, 404, 'NOT_FOUND', $previous);
    }
}
