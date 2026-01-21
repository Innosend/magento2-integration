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

namespace Innosend\Integration\Api;

/**
 * Interface for Carrier API
 *
 * @api
 */
interface CarrierInterface
{
    /**
     * Get available carriers from Innosend
     *
     * @return array List of carriers with id, name, and code
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCarriers(): array;
}


















