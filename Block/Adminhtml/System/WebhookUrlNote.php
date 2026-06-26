<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Block\Adminhtml\System;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class WebhookUrlNote
 *
 * @package MaGuru\MonoCore\Block\Adminhtml\System
 */
class WebhookUrlNote extends Field
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $urlPath = (string) ($this->getData('url_path') ?? '');
        /** @var \Magento\Store\Model\Store $store */
        $store   = $this->_storeManager->getStore();
        $fullUrl = rtrim($store->getBaseUrl(), '/') . '/' . ltrim($urlPath, '/');

        return '<code style="word-break:break-all">' . $this->escapeHtml($fullUrl) . '</code>'
            . '<p class="note"><span>'
            . $this->escapeHtml((string) __('Enter this URL in Monobank Cabinet as the Webhook URL.'))
            . '</span></p>';
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsetData('scope');
        $element->unsetData('can_use_website_value');
        $element->unsetData('can_use_default_value');
        return parent::render($element);
    }
}
