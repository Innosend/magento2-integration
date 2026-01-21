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
 * Controller for testing API connection
 */
class TestConnection extends Action implements HttpPostActionInterface
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

            // Check if API Key/Secret is provided
            $hasApiKey = false;
            $hasApiSecret = false;
            
            $paramsKey = $params['api_key'] ?? '';
            $paramsSecret = $params['api_secret'] ?? '';
            
            if (!empty($paramsKey) && !preg_match('/^\*+$/', $paramsKey)) {
                $hasApiKey = true;
            } elseif (preg_match('/^\*+$/', $paramsKey)) {
                $configKey = $this->innosendConfig->getApiKey($storeId);
                $hasApiKey = !empty($configKey);
            }
            
            if (!empty($paramsSecret) && !preg_match('/^\*+$/', $paramsSecret)) {
                $hasApiSecret = true;
            } elseif (preg_match('/^\*+$/', $paramsSecret)) {
                $configSecret = $this->innosendConfig->getApiSecret($storeId);
                $hasApiSecret = !empty($configSecret);
            }

            if (!($hasApiKey && $hasApiSecret)) {
                if (!$hasApiKey) {
                    throw new LocalizedException(__('API Key is required.'));
                }
                if ($hasApiKey && !$hasApiSecret) {
                    throw new LocalizedException(__('API Secret is required when using API Key.'));
                }
                if (!$hasApiKey && $hasApiSecret) {
                    throw new LocalizedException(__('API Key is required when using API Secret.'));
                }
            }

            $apiKey = $params['api_key'] ?? '';
            if (preg_match('/^\*+$/', $apiKey)) {
                $apiKey = $this->innosendConfig->getApiKey($storeId);
            } else {
                try {
                    $decrypted = $this->encryptor->decrypt($apiKey);
                    if (!empty($decrypted)) {
                        $apiKey = $decrypted;
                    }
                } catch (\Exception $e) {
                    // Key is not encrypted, use as is
                }
            }

            $apiSecret = $params['api_secret'] ?? '';
            if (preg_match('/^\*+$/', $apiSecret)) {
                $apiSecret = $this->innosendConfig->getApiSecret($storeId);
            } else {
                try {
                    $decrypted = $this->encryptor->decrypt($apiSecret);
                    if (!empty($decrypted)) {
                        $apiSecret = $decrypted;
                    }
                } catch (\Exception $e) {
                    // Secret is not encrypted, use as is
                }
            }

            $mode = $params['mode'] ?? 'test';
            $endpoint = $mode === 'production' 
                ? ($params['live_endpoint'] ?? 'https://api.innosend.eu')
                : ($params['test_endpoint'] ?? 'https://api-staging.innosend.eu');

            $result['debug'] = [
                'auth_method' => 'Basic Auth (API Key/Secret)',
                'credentials_detected' => ($hasApiKey && $hasApiSecret) ? 'Yes' : 'No',
                'api_key' => $this->maskValue($apiKey),
                'api_key_length' => strlen($apiKey),
                'api_secret' => $this->maskValue($apiSecret),
                'api_secret_length' => strlen($apiSecret),
                'endpoint' => $endpoint,
                'mode' => $mode,
                'timeout' => (int)($params['timeout'] ?? 30),
            ];

            $timeout = (int)($params['timeout'] ?? 30);

            $testUrl = rtrim($endpoint, '/');
            
            $parsedUrl = parse_url($endpoint);
            $hasPath = !empty($parsedUrl['path']) && $parsedUrl['path'] !== '/';
            
            if ($hasPath) {
                $testEndpoints = [$testUrl];
            } else {
                if (!empty($params['organization_id'])) {
                    $testUrl .= '/organizations/' . trim($params['organization_id'], '/');
                }

                $testEndpoints = [
                    $testUrl . '/integration/webshopapi/configuration',
                    $testUrl . '/ping',
                    $testUrl . '/health',
                    $testUrl . '/api/ping',
                    $testUrl
                ];
            }

            $lastError = null;
            $lastHttpCode = 0;

            foreach ($testEndpoints as $url) {
                $headers = [
                    'Content-Type: application/json',
                    'Accept: application/json',
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
                    CURLOPT_USERPWD => $apiKey . ':' . $apiSecret,
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
                    $result['message'] = __('Connection successful! API Key and API Secret are valid.');
                    $result['debug']['tested_url'] = $url;
                    $result['debug']['http_code'] = $httpCode;
                    $result['debug']['response'] = substr($response, 0, 500);
                    break;
                }

                if ($httpCode === 401 || $httpCode === 403) {
                    $result['success'] = false;
                    $result['errorMessage'] = __('Connection failed: Invalid API credentials (HTTP %1). Please check your API Key and API Secret.', $httpCode);
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
                        $result['errorMessage'] = __('Invalid API credentials (HTTP %1). Please check your API Key and API Secret.', $lastHttpCode);
                    } else {
                        $result['success'] = false;
                        $result['errorMessage'] = __('Connection failed. HTTP Status: %1. Please verify the endpoint URL and credentials.', $lastHttpCode);
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

