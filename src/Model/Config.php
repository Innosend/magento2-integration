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
    public const XML_PATH_ENABLED = 'innosend_api/configuration/enabled';
    public const XML_PATH_MODE = 'innosend_api/configuration/mode';
    public const XML_PATH_ALLOW_MUTATIONS = 'innosend_api/configuration/allow_mutations';
    public const XML_PATH_ORGANIZATION_ID = 'innosend_api/configuration/organization_id';
    public const XML_PATH_API_KEY = 'innosend_api/configuration/api_key';
    public const XML_PATH_API_SECRET = 'innosend_api/configuration/api_secret';
    public const XML_PATH_TEST_ENDPOINT = 'innosend_api/configuration/test_endpoint';
    public const XML_PATH_LIVE_ENDPOINT = 'innosend_api/configuration/live_endpoint';
    public const XML_PATH_TIMEOUT = 'innosend_api/configuration/timeout';
    public const XML_PATH_PICKUP_POINTS_API_TOKEN = 'innosend_api/configuration/pickup_points/api_token';

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
     * Whether write operations (POST/PUT/DELETE) to the Innosend API are allowed.
     * This is a safety switch to prevent accidental live mutations while testing.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isMutationAllowed(?int $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_ALLOW_MUTATIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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
     * Get API key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiKey(?int $storeId = null): string
    {
        $encryptedKey = (string) $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->encryptor->decrypt($encryptedKey);
    }

    /**
     * Get API secret
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiSecret(?int $storeId = null): string
    {
        $encryptedSecret = (string) $this->scopeConfig->getValue(
            self::XML_PATH_API_SECRET,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->encryptor->decrypt($encryptedSecret);
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

        if (empty($baseUrl)) {
            $baseUrl = 'https://api.innosend.eu';
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
     * Get Pickup Points API Token
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPickupPointsApiToken(?int $storeId = null): string
    {
        $encryptedToken = (string) $this->scopeConfig->getValue(
            self::XML_PATH_PICKUP_POINTS_API_TOKEN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($encryptedToken)) {
            return '';
        }

        try {
            $decrypted = $this->encryptor->decrypt($encryptedToken);
            if (!empty($decrypted) && $decrypted !== $encryptedToken) {
                return trim($decrypted);
            }
            return trim($decrypted);
        } catch (\Exception $e) {
            return trim($encryptedToken);
        }
    }

    /**
     * Check if configuration is valid
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isValid(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId)
            && !empty($this->getApiKey($storeId))
            && !empty($this->getApiSecret($storeId))
            && !empty($this->getEndpointUrl($storeId));
    }
}
