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
 * Interface for Innosend API client
 *
 * @api
 */
interface ClientInterface
{
    /**
     * Make a GET request to the API
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array Response data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get(string $endpoint, array $params = []): array;

    /**
     * Make a POST request to the API
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function post(string $endpoint, array $data = []): array;

    /**
     * Make a PUT request to the API
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function put(string $endpoint, array $data = []): array;

    /**
     * Make a DELETE request to the API
     *
     * @param string $endpoint API endpoint
     * @return array Response data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(string $endpoint): array;

    /**
     * Check if API is enabled and configured
     *
     * @return bool
     */
    public function isEnabled(): bool;
}


