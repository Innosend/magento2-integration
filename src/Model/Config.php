<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.1.0
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Model;

use Innosend\Integration\Model\Config\Source\Mode;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration model for Innosend Integration module
 */
class Config
{
    /**
     * Configuration paths
     */
    public const XML_PATH_ENABLED     = 'innosend_api/configuration/enabled';
    public const XML_PATH_MODE        = 'innosend_api/configuration/mode';
    public const XML_PATH_ORGANIZATION_ID = 'innosend_api/configuration/organization_id';
    public const XML_PATH_API_TOKEN   = 'innosend_api/configuration/api_token';
    public const XML_PATH_TEST_ENDPOINT  = 'innosend_api/configuration/test_endpoint';
    public const XML_PATH_LIVE_ENDPOINT  = 'innosend_api/configuration/live_endpoint';
    public const XML_PATH_TIMEOUT     = 'innosend_api/configuration/timeout';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get API mode (test or live)
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMode(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: Mode::MODE_TEST;
    }

    /**
     * Get Organization ID
     *
     * @param int|null $storeId
     * @return string
     */
    public function getOrganizationId(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_ORGANIZATION_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '';
    }

    /**
     * Get API Token for Bearer authentication
     * Used for all Innosend API endpoints (including /v1/pickup-point).
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiToken(?int $storeId = null): string
    {
        $encryptedToken = (string) $this->scopeConfig->getValue(
            self::XML_PATH_API_TOKEN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($encryptedToken)) {
            return '';
        }

        try {
            $decrypted = $this->encryptor->decrypt($encryptedToken);
            return trim($decrypted);
        } catch (\Exception $e) {
            return trim($encryptedToken);
        }
    }

    /**
     * Get API endpoint base URL based on mode
     *
     * @param int|null $storeId
     * @return string
     */
    public function getEndpointUrl(?int $storeId = null): string
    {
        $mode = $this->getMode($storeId);
        $path = $mode === Mode::MODE_LIVE
            ? self::XML_PATH_LIVE_ENDPOINT
            : self::XML_PATH_TEST_ENDPOINT;

        $baseUrl = (string) $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $baseUrl = trim($baseUrl);
        if (empty($baseUrl)) {
            $baseUrl = 'https://api.innosend.eu';
        }

        // The Innosend API only serves https; http or a missing scheme causes a 301 on every call
        if (strpos($baseUrl, '://') === false) {
            $baseUrl = 'https://' . $baseUrl;
        } elseif (stripos($baseUrl, 'http://') === 0
            && preg_match('#^http://([a-z0-9-]+\.)*innosend\.eu(/|:|$)#i', $baseUrl)
        ) {
            $baseUrl = 'https://' . substr($baseUrl, 7);
        }

        return rtrim($baseUrl, '/');
    }

    /**
     * Get organization path prefix for API endpoints
     * Returns "organizations/{organizationId}" or empty string if not configured
     *
     * @param int|null $storeId
     * @return string
     */
    public function getOrganizationPath(?int $storeId = null): string
    {
        $organizationId = $this->getOrganizationId($storeId);

        if (empty($organizationId)) {
            return '';
        }

        return 'organizations/' . trim($organizationId, '/');
    }

    /**
     * Get request timeout in seconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getTimeout(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_TIMEOUT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 30;
    }

    /**
     * Check if the API is properly configured (enabled + API Token present).
     * All endpoints use Bearer token authentication.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isValid(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId)
            && !empty($this->getApiToken($storeId))
            && !empty($this->getEndpointUrl($storeId));
    }
}
