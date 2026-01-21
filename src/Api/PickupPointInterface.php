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
 * Interface for Pickup Points API
 *
 * @api
 */
interface PickupPointInterface
{
    /**
     * Get pickup points by address
     *
     * @param string $street Street address
     * @param string $postcode Postal code
     * @param string $city City
     * @param string $countryCode Country code (ISO 3166-1 alpha-2)
     * @param string|null $carrier Carrier code (optional)
     * @return array List of pickup points
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPickupPoints(
        string $street,
        string $postcode,
        string $city,
        string $countryCode,
        ?string $carrier = null
    ): array;
}


