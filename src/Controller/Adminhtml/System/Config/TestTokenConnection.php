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

namespace Innosend\Integration\Controller\Adminhtml\System\Config;

use Innosend\Integration\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filter\StripTags;

/**
 * Controller for testing API Token connection
 */
class TestTokenConnection extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Innosend_Integration::config';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Config
     */
    private $innosendConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var StripTags
     */
    private $tagFilter;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Config $innosendConfig
     * @param EncryptorInterface $encryptor
     * @param StripTags $tagFilter
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Config $innosendConfig,
        EncryptorInterface $encryptor,
        StripTags $tagFilter
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->innosendConfig = $innosendConfig;
        $this->encryptor = $encryptor;
        $this->tagFilter = $tagFilter;
    }

    /**
     * Mask sensitive value for debug output
     *
     * @param string $value
     * @return string
     */
    private function maskValue(string $value): string
    {
        if (empty($value)) {
            return '';
        }
        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        return substr($value, 0, 2) . str_repeat('*', $length - 4) . substr($value, -2);
    }

    /**
     * Execute test connection
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = [
            'success' => false,
            'errorMessage' => '',
        ];

        $params = $this->getRequest()->getParams();
        $storeId = $this->getRequest()->getParam('store', null);

        try {
            if (empty($params['enabled']) || $params['enabled'] != '1') {
                throw new LocalizedException(__('API must be enabled to test connection.'));
            }

            $apiToken = $params['api_token'] ?? '';

            if (empty($apiToken) || preg_match('/^\*+$/', $apiToken)) {
                $apiToken = $this->innosendConfig->getApiToken($storeId);
            } else {
                // New value provided, try to decrypt if encrypted
                try {
                    $decrypted = $this->encryptor->decrypt($apiToken);
                    if (!empty($decrypted) && $decrypted !== $apiToken) {
                        $apiToken = $decrypted;
                    }
                } catch (\Exception $e) {
                    // Token is not encrypted, use as is
                }
            }

            // Trim whitespace from token
            $apiToken = trim($apiToken);
            $apiToken = preg_replace('/\s+/', '', $apiToken);

            if (empty($apiToken)) {
                throw new LocalizedException(__('API Token is required. Please enter your API Token in the configuration first.'));
            }

            // Temporarily set configuration values for testing
            $mode = $params['mode'] ?? 'test';
            $endpoint = $mode === 'production' 
                ? ($params['live_endpoint'] ?? 'https://api.innosend.eu')
                : ($params['test_endpoint'] ?? 'https://api.innosend.eu');
            
            // Store API credentials for debugging in response
            $result['debug'] = [
                'api_token' => $this->maskValue($apiToken),
                'api_token_length' => strlen($apiToken),
                'endpoint' => $endpoint,
                'mode' => $mode,
                'timeout' => (int)($params['timeout'] ?? 30),
                'auth_method' => 'Bearer Token (API Token)',
            ];

            $timeout = (int)($params['timeout'] ?? 30);

            // Build test URL
            $testUrl = rtrim($endpoint, '/');
            
            // If organization ID is provided, append it
            if (!empty($params['organization_id'])) {
                $testUrl .= '/organizations/' . trim($params['organization_id'], '/');
            }

            // Use the specific endpoint for API Token testing
            $testEndpoints = [$testUrl . '/v1/pickup-point/courier/'];

            $lastError = null;
            $lastHttpCode = 0;

            foreach ($testEndpoints as $url) {
                $headers = [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $apiToken,
                ];

                $ch = curl_init($url);
                $curlOptions = [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTPHEADER => $headers,
                ];

                curl_setopt_array($ch, $curlOptions);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                $lastHttpCode = $httpCode;
                $lastError = $error;

                if ($httpCode >= 200 && $httpCode < 300) {
                    $result['success'] = true;
                    $result['message'] = __('Connection successful! API Token is valid.');
                    $result['debug']['tested_url'] = $url;
                    $result['debug']['http_code'] = $httpCode;
                    $result['debug']['response'] = substr($response, 0, 500);
                    break;
                }

                if ($httpCode === 401 || $httpCode === 403) {
                    $result['success'] = false;
                    $result['errorMessage'] = __('Connection failed: Invalid API Token (HTTP %1). Please check your API Token at https://dashboard.innosend.eu/settings/api-keys', $httpCode);
                    break;
                }

                if ($httpCode === 404) {
                    continue;
                }

                if ($httpCode >= 400) {
                    continue;
                }
            }

            if (!isset($result['success'])) {
                $result['debug']['tested_urls'] = $testEndpoints;
                $result['debug']['last_http_code'] = $lastHttpCode;
                $result['debug']['last_error'] = $lastError;
                
                if ($lastError) {
                    $result['success'] = false;
                    $result['errorMessage'] = __('Connection error: %1', $lastError);
                } elseif ($lastHttpCode >= 400) {
                    if ($lastHttpCode === 401 || $lastHttpCode === 403) {
                        $result['success'] = false;
                        $result['errorMessage'] = __('Invalid API Token (HTTP %1). Please check your API Token at https://dashboard.innosend.eu/settings/api-keys', $lastHttpCode);
                    } else {
                        $result['success'] = false;
                        $result['errorMessage'] = __('Connection failed. HTTP Status: %1. Please verify the endpoint URL and API Token.', $lastHttpCode);
                    }
                } else {
                    $result['success'] = false;
                    $result['errorMessage'] = __('Unable to connect to API endpoint. Please verify the endpoint URL and network connectivity.');
                }
            }

        } catch (LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
        } catch (\Exception $e) {
            $message = __($e->getMessage());
            $result['errorMessage'] = $this->tagFilter->filter($message);
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }
}

