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
 * Interface for Order API
 *
 * @api
 */
interface OrderInterface
{
    /**
     * Create or update an order in Innosend
     *
     * @param array $orderData Order data
     * @return array Response data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOrder(array $orderData): array;

    /**
     * Get order status from Innosend
     *
     * @param string $orderId Innosend order ID
     * @return array Order status data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrderStatus(string $orderId): array;

    /**
     * Get tracking information for an order
     *
     * @param string $orderId Innosend order ID
     * @return array Tracking data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTrackingInfo(string $orderId): array;
}


