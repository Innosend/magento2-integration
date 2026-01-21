<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.0.0
 * @date        2025-11-28
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for API mode selection
 */
class Mode implements OptionSourceInterface
{
    /**
     * Mode constants
     */
    public const MODE_TEST = 'test';
    public const MODE_LIVE = 'production';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::MODE_TEST, 'label' => __('Test')],
            ['value' => self::MODE_LIVE, 'label' => __('Production')],
        ];
    }
}


