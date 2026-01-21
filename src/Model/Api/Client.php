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

namespace Innosend\Integration\Model\Api;

use Innosend\Integration\Api\ClientInterface;
use Innosend\Integration\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * API client implementation for Innosend
 */
class Client implements ClientInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string|null
     */
    private $lastRequestUrl = null;

    /**
     * @param Config $config
     * @param Curl $curl
     * @param Json $json
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        Curl $curl,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Get last request URL (for debugging)
     *
     * @return string|null
     */
    public function getLastRequestUrl(): ?string
    {
        return $this->lastRequestUrl;
    }

    /**
     * Make a GET request to the API
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array Response data
     * @throws LocalizedException
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * Make a POST request to the API
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     * @throws LocalizedException
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [], $data);
    }

    /**
     * Make a PUT request to the API
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     * @throws LocalizedException
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, [], $data);
    }

    /**
     * Make a DELETE request to the API
     *
     * @param string $endpoint API endpoint
     * @return array Response data
     * @throws LocalizedException
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Check if API is enabled and configured
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isValid();
    }

    /**
     * Make HTTP request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @param array $data Request body data
     * @return array Response data
     * @throws LocalizedException
     */
    private function request(string $method, string $endpoint, array $params = [], array $data = []): array
    {
        if (!$this->isEnabled()) {
            throw new LocalizedException(__('Innosend API is not enabled or not properly configured.'));
        }

        // Safety switch: block any write operations unless explicitly allowed in config.
        // This lets you test connectivity (GET) without risking accidental live mutations.
        if ($method !== 'GET' && !$this->config->isMutationAllowed()) {
            $this->logger->warning('Innosend API write operation blocked by configuration', [
                'method' => $method,
                'endpoint' => $endpoint,
            ]);
            throw new LocalizedException(
                __('Innosend API write operations are disabled (enable "Allow write operations" to proceed).')
            );
        }

        $baseUrl = $this->config->getEndpointUrl();
        $organizationPath = $this->config->getOrganizationPath();
        
        // Build the full endpoint path
        $endpointPath = ltrim($endpoint, '/');
        
        // Check if this is a pickup points endpoint (should NOT use organization path)
        $isPickupPointEndpoint = strpos($endpointPath, 'pickup-point') !== false;
        
        // Ensure we use /v1 prefix (WordPress plugin format)
        // Pickup points endpoints do NOT use organization path (they're global)
        if ($isPickupPointEndpoint) {
            // If endpoint already starts with v1/, use it as-is
            if (strpos($endpointPath, 'v1/') === 0) {
                // Already has v1 prefix, use as-is
            } elseif (strpos($endpointPath, '/v1/') !== false) {
                // Has v1 somewhere in the path, use as-is
            } else {
                // Add v1 prefix for pickup points
                $endpointPath = 'v1/' . $endpointPath;
            }
        } else {
            // Non-pickup endpoints also live under /v1 (e.g. /v1/orders)
            if (strpos($endpointPath, 'v1/') === 0) {
                // Already has v1 prefix, use as-is
            } elseif (strpos($endpointPath, '/v1/') !== false) {
                // Has v1 somewhere in the path, use as-is
            } else {
                $endpointPath = 'v1/' . $endpointPath;
            }
        }
        
        // Only add organization path for non-pickup-point endpoints
        // Pickup points are global and don't require organization ID
        if (!empty($organizationPath) && !$isPickupPointEndpoint) {
            // If endpoint is versioned (v1/...), the organization prefix should come after v1/
            if (strpos($endpointPath, 'v1/') === 0) {
                if (strpos($endpointPath, 'v1/organizations/') !== 0) {
                    $endpointPath = 'v1/' . $organizationPath . '/' . substr($endpointPath, 3);
                }
            } elseif (strpos($endpointPath, 'organizations/') !== 0) {
                // Fallback for non-versioned endpoints
                $endpointPath = $organizationPath . '/' . $endpointPath;
            }
        }
        
        $url = rtrim($baseUrl, '/') . '/' . $endpointPath;

        if (!empty($params)) {
            // WordPress plugin formats array parameters as key=value1&key=value2 (not key[]=value1)
            // Build query string manually to match WordPress plugin format
            $queryParts = [];
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    // For arrays, add each item separately: couriers=value1&couriers=value2
                    foreach ($value as $item) {
                        $queryParts[] = urlencode($key) . '=' . urlencode((string) $item);
                    }
                } else {
                    $queryParts[] = urlencode($key) . '=' . urlencode((string) $value);
                }
            }
            $url .= '?' . implode('&', $queryParts);
        }

        // Store the full URL for debugging
        $this->lastRequestUrl = $url;

        try {
            $this->curl->setTimeout($this->config->getTimeout());
            $this->curl->addHeader('Content-Type', 'application/json');
            
            // Use Bearer token authentication (like WordPress plugin)
            // For pickup points endpoints, use the pickup points specific token
            $apiToken = null;
            if (strpos($endpointPath, 'pickup-point') !== false) {
                // This is a pickup points endpoint, use pickup points API token
                $apiToken = $this->config->getPickupPointsApiToken();
                $this->logger->debug('Using pickup points API token for endpoint', ['endpoint' => $endpointPath]);
            }
            
            // Use Bearer token if available (pickup points), otherwise use Basic Auth
            if (!empty($apiToken)) {
                $this->curl->addHeader('Authorization', 'Bearer ' . $apiToken);
            } else {
                // Use Basic Auth for all other endpoints (orders, etc.)
                $apiKey = $this->config->getApiKey();
                $apiSecret = $this->config->getApiSecret();
                if (!empty($apiKey) && !empty($apiSecret)) {
                    $this->curl->setOption(CURLOPT_USERPWD, $apiKey . ':' . $apiSecret);
                    $this->logger->debug('Using Basic Auth (API key/secret) for endpoint', ['endpoint' => $endpointPath]);
                }
            }

            if (!empty($data)) {
                $this->curl->addHeader('Content-Length', (string) strlen($this->json->serialize($data)));
            }

            switch ($method) {
                case 'GET':
                    $this->curl->get($url);
                    break;
                case 'POST':
                    $this->curl->post($url, $this->json->serialize($data));
                    break;
                case 'PUT':
                    $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
                    $this->curl->post($url, $this->json->serialize($data));
                    break;
                case 'DELETE':
                    $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
                    $this->curl->get($url);
                    break;
            }

            $responseBody = $this->curl->getBody();
            $statusCode = $this->curl->getStatus();

            if ($statusCode >= 400) {
                $this->logger->error(
                    'Innosend API Error',
                    [
                        'method' => $method,
                        'url' => $url,
                        'status' => $statusCode,
                        'response' => $responseBody,
                    ]
                );

                throw new LocalizedException(
                    __('Innosend API error: %1', $responseBody)
                );
            }

            // Handle empty response
            if (empty($responseBody)) {
                $this->logger->warning('Empty response from API', [
                    'method' => $method,
                    'url' => $url,
                    'status' => $statusCode,
                ]);
                return [];
            }

            try {
                $decoded = $this->json->unserialize($responseBody);
                return is_array($decoded) ? $decoded : [];
            } catch (\Exception $e) {
                $this->logger->error('Failed to decode JSON response', [
                    'method' => $method,
                    'url' => $url,
                    'response' => substr($responseBody, 0, 500),
                    'error' => $e->getMessage(),
                ]);
                throw new LocalizedException(
                    __('Invalid JSON response from Innosend API: %1', $e->getMessage())
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Innosend API Exception',
                [
                    'method' => $method,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]
            );

            if ($e instanceof LocalizedException) {
                throw $e;
            }

            throw new LocalizedException(
                __('Error communicating with Innosend API: %1', $e->getMessage())
            );
        }
    }
}
