<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ChastEnvironment
 *
 * @package MaGuru\MonoCore\Model\Config\Source
 */
class ChastEnvironment implements OptionSourceInterface
{
    /**
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'production', 'label' => 'Production (u2.monobank.com.ua)'],
            ['value' => 'sandbox',    'label' => 'Sandbox / Test (u2-demo-ext.mono.st4g3.com)'],
            ['value' => 'stage',      'label' => 'Stage / Pre-production (u2-ext.mono.st4g3.com)'],
        ];
    }
}
