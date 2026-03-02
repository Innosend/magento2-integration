<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.1.0
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;

/**
 * Test API Token connection button block
 */
class TestTokenConnection extends Field
{
    /**
     * Set template
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Innosend_Integration::system/config/testtokenconnection.phtml');
        return $this;
    }

    /**
     * Unset non-related element parameters
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element = clone $element;
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get button and scripts HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData() ?? [];
        $this->addData([
            'button_label'  => __($originalData['button_label'] ?? 'Test API Token Connection'),
            'html_id'       => $element->getHtmlId(),
            'ajax_url'      => $this->_urlBuilder->getUrl('innosend/system_config/testtokenconnection'),
            'field_mapping' => str_replace('"', '\\"', json_encode($this->_getFieldMapping())),
        ]);

        return $this->_toHtml();
    }

    /**
     * Returns the Magento form field IDs needed for the AJAX request.
     * Field IDs follow the pattern: {section}_{group}_{field}
     *
     * @return array
     */
    protected function _getFieldMapping(): array
    {
        return [
            'enabled'      => 'innosend_api_configuration_enabled',
            'mode'         => 'innosend_api_configuration_mode',
            'organization_id' => 'innosend_api_configuration_organization_id',
            'api_token'    => 'innosend_api_configuration_api_token',
            'test_endpoint' => 'innosend_api_configuration_test_endpoint',
            'live_endpoint' => 'innosend_api_configuration_live_endpoint',
            'timeout'      => 'innosend_api_configuration_timeout',
        ];
    }

    /**
     * Get field mapping as JSON string
     *
     * @return string
     */
    public function getFieldMapping(): string
    {
        return $this->getData('field_mapping') ?? '';
    }

    /**
     * Get AJAX URL
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getData('ajax_url') ?? '';
    }

    /**
     * Get button label
     *
     * @return string
     */
    public function getButtonLabel(): string
    {
        $label = $this->getData('button_label');
        if ($label instanceof \Magento\Framework\Phrase) {
            return (string) $label;
        }
        return (string) ($label ?? '');
    }
}
