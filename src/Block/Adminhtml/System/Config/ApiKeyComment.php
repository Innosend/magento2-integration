<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.0.0
 * @date        2025-11-27
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Innosend\Integration\Model\Config as InnosendConfig;

/**
 * Block to show masked API key in comment
 */
class ApiKeyComment extends Field
{
    /**
     * @var InnosendConfig
     */
    private $innosendConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param InnosendConfig $innosendConfig
     * @param array $data
     * @param \Magento\Framework\View\Helper\SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        InnosendConfig $innosendConfig,
        array $data = [],
        ?\Magento\Framework\View\Helper\SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
        $this->innosendConfig = $innosendConfig;
    }

    /**
     * Render field HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        try {
        // Get the masked API key value
            $storeId = $this->getRequest() ? $this->getRequest()->getParam('store', null) : null;
        $apiKey = $this->innosendConfig->getApiKey($storeId);

        // Update the comment to show masked value
        if (!empty($apiKey)) {
            $maskedKey = $this->maskValue($apiKey);
                $originalComment = $element->getComment() ?? '';
            $newComment = $originalComment . '<br><br><strong>Current value:</strong> <code>' . htmlspecialchars($maskedKey) . '</code>';
            $element->setComment($newComment);
            }
        } catch (\Exception $e) {
            // Silently fail if there's an error getting the key
            // Just render the element normally
        }

        return parent::_getElementHtml($element);
    }

    /**
     * Mask a value showing first 2 and last 4 characters
     *
     * @param string $value
     * @return string
     */
    private function maskValue(string $value): string
    {
        if (empty($value) || strlen($value) <= 6) {
            return str_repeat('*', strlen($value));
        }

        $first = substr($value, 0, 2);
        $last = substr($value, -4);
        $middle = str_repeat('*', max(4, strlen($value) - 6));

        return $first . $middle . $last;
    }
}
